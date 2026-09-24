<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\AuditReceipt;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoterToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TpsSyncController extends Controller
{
    /**
     * Dipanggil Electron (tombol "Tarik Data DPT") selagi online, idealnya H-1 atau
     * pagi hari-H, supaya cache lokal siap dipakai kalau internet putus di tengah acara.
     */
    public function snapshot(string $kodeTps): JsonResponse
    {
        /** @var Tps $tps */
        $tps = request()->user(); // sudah dipastikan cocok dengan $kodeTps oleh middleware

        $voters = $tps->election->voters()
            ->select('id', 'nik_hash', 'nama', 'alamat', 'status_verifikasi')
            ->get();

        $positions = $tps->election->positions()
            ->with(['candidates' => fn($q) => $q->select('id', 'position_id', 'nama', 'nomor_urut', 'foto')])
            ->orderBy('urutan')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'urutan' => $p->urutan,
                'candidates' => $p->candidates->map(fn($c) => [
                    'id' => $c->id,
                    'nomor_urut' => $c->nomor_urut,
                    'nama' => $c->nama,
                    // Foto TIDAK dikirim sebagai base64 di sini (bisa besar & bikin snapshot
                    // lambat). Kalau device butuh foto offline, unduh terpisah lewat
                    // GET /storage/{path} selagi online, simpan lokal, baca dari situ.
                    'foto_base64' => null,
                ]),
            ]);

        AuditLog::catat('device_pull_snapshot', 'tps', $tps->id);

        return response()->json([
            'voters' => $voters,
            'positions' => $positions,
        ]);
    }

    /**
     * Dipanggil Electron tiap kali ada data baru untuk dikirim (otomatis tiap 2 menit,
     * atau manual lewat tombol "Sinkron Sekarang"). Idempotent — aman dipanggil ulang
     * dengan payload yang sama tanpa membuat data duplikat.
     */
    public function sync(Request $request, string $kodeTps): JsonResponse
    {
        /** @var Tps $tps */
        $tps = $request->user();

        $data = $request->validate([
            'votes' => ['array'],
            'votes.*.position_id_pusat' => ['required_with:votes', 'integer', 'exists:positions,id'],
            'votes.*.candidate_id_pusat' => ['required_with:votes', 'integer', 'exists:candidates,id'],
            'votes.*.token_hash' => ['required_with:votes', 'string'],
            'votes.*.batch_code' => ['required_with:votes', 'string'],
            'votes.*.nomor_struk' => ['required_with:votes', 'string'],
            'votes.*.waktu_vote' => ['required_with:votes', 'date'],

            'tokens' => ['array'],
            'tokens.*.voter_id_pusat' => ['required_with:tokens', 'integer', 'exists:voters,id'],
            'tokens.*.token_hash' => ['required_with:tokens', 'string'],
            'tokens.*.status' => ['required_with:tokens', 'in:aktif,terpakai,kedaluwarsa'],
            'tokens.*.dibuat_pada' => ['required_with:tokens', 'date'],
            'tokens.*.kedaluwarsa_pada' => ['required_with:tokens', 'date'],
            'tokens.*.dipakai_pada' => ['nullable', 'date'],
        ]);

        $dibuatOleh = User::where('tps_id', $tps->id)->where('role', 'petugas_tps')->value('id')
            ?? User::where('role', 'admin')->value('id');

        $jumlahVoteMasuk = 0;
        $jumlahTokenMasuk = 0;

        DB::transaction(function () use ($data, $tps, $dibuatOleh, &$jumlahVoteMasuk, &$jumlahTokenMasuk) {
            foreach ($data['tokens'] ?? [] as $t) {
                VoterToken::updateOrCreate(
                    ['token_hash' => $t['token_hash']], // kunci pencocokan — cegah duplikat kalau sync diulang
                    [
                        'voter_id' => $t['voter_id_pusat'],
                        'tps_id' => $tps->id, // selalu dari device yang terautentikasi, JANGAN percaya payload
                        'status' => $t['status'],
                        'dibuat_oleh' => $dibuatOleh,
                        'kedaluwarsa_pada' => $t['kedaluwarsa_pada'],
                        'dipakai_pada' => $t['dipakai_pada'] ?? null,
                    ]
                );
                $jumlahTokenMasuk++;

                // Pemilih yang tokennya sudah pernah dipakai berarti sudah check-in —
                // pastikan status di tabel voters pusat juga ikut ter-refresh.
                if ($t['status'] !== 'aktif') {
                    \App\Models\Voter::where('id', $t['voter_id_pusat'])
                        ->where('status_verifikasi', '!=', 'ditolak')
                        ->update(['status_verifikasi' => 'terverifikasi', 'tps_id' => $tps->id]);
                }
            }

            foreach ($data['votes'] ?? [] as $v) {
                $vote = Vote::firstOrCreate(
                    [
                        // Unique constraint asli di tabel votes — kunci yang sama dipakai di sini
                        'position_id' => $v['position_id_pusat'],
                        'token_hash' => $v['token_hash'],
                    ],
                    [
                        'candidate_id' => $v['candidate_id_pusat'],
                        'tps_id' => $tps->id,
                        'waktu_vote' => $v['waktu_vote'],
                        'synced' => true,
                        'synced_at' => now(),
                    ]
                );
                $jumlahVoteMasuk++;

                AuditReceipt::firstOrCreate(
                    ['nomor_struk' => $v['nomor_struk']],
                    [
                        'vote_id' => $vote->id,
                        'qr_verifikasi' => $v['batch_code'],
                        'dicetak_pada' => $v['waktu_vote'],
                        'status_audit' => 'belum_dicek',
                    ]
                );
            }

            $tps->update(['status_koneksi' => 'online', 'terakhir_online' => now()]);
        });

        AuditLog::catat('device_push_sync', 'tps', $tps->id, null, [
            'votes' => $jumlahVoteMasuk,
            'tokens' => $jumlahTokenMasuk,
        ]);

        return response()->json([
            'sukses' => true,
            'votes_diterima' => $jumlahVoteMasuk,
            'tokens_diterima' => $jumlahTokenMasuk,
        ]);
    }
}
