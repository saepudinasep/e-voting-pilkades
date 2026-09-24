<?php

namespace App\Http\Controllers;

use App\Models\AuditReceipt;
use App\Models\Tps;
use App\Models\Vote;
use App\Models\VoterToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BilikSuaraController extends Controller
{
    /** Langkah 1: layar awal bilik suara — minta token/QR. */
    public function tokenEntry(string $kodeTps): Response
    {
        $tps = Tps::where('kode_tps', $kodeTps)->with('election')->firstOrFail();

        return Inertia::render('Bilik/TokenEntry', [
            'tps' => $tps,
        ]);
    }

    /** Validasi token, simpan ke session, lanjut ke halaman pilih kandidat. */
    public function masuk(Request $request, string $kodeTps): RedirectResponse
    {
        $tps = Tps::where('kode_tps', $kodeTps)->firstOrFail();

        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = VoterToken::findValid($request->input('token'), $tps->id);

        if (! $token) {
            return back()->with('error', 'Token tidak valid, sudah dipakai, atau sudah kedaluwarsa. Hubungi petugas TPS.');
        }

        // Regenerasi session ID setelah token tervalidasi — cegah session fixation
        // (mencegah orang lain menebak/mencuri session sebelum token dimasukkan).
        $request->session()->regenerate();
        $request->session()->put('bilik_token_id_' . $tps->id, $token->id);

        return redirect()->route('bilik.pilih', $kodeTps);
    }

    /** Langkah 2: tampilkan surat suara digital. */
    public function pilih(Request $request, string $kodeTps): Response|RedirectResponse
    {
        $tps = Tps::where('kode_tps', $kodeTps)->with('election.positions.candidates')->firstOrFail();

        $tokenId = $request->session()->get('bilik_token_id_' . $tps->id);
        $token = $tokenId ? VoterToken::find($tokenId) : null;

        if (! $token || ! $token->isValid() || $token->tps_id !== $tps->id) {
            return redirect()->route('bilik.masuk', $kodeTps)
                ->with('error', 'Sesi token tidak ditemukan atau sudah berakhir, silakan scan ulang.');
        }

        return Inertia::render('Bilik/Ballot', [
            'tps' => $tps,
            'positions' => $tps->election->positions()->with('candidates')->orderBy('urutan')->get(),
        ]);
    }

    /**
     * Langkah 3: submit pilihan, catat suara + cetak struk audit.
     *
     * Dibungkus DB::transaction() + lockForUpdate() supaya kalau ada 2 request submit
     * dengan token yang sama datang hampir bersamaan (double-click, request diulang
     * manual, atau percobaan replay), hanya SATU yang berhasil — request kedua akan
     * menunggu giliran lock, lalu melihat status token sudah 'terpakai' dan ditolak.
     */
    public function submit(Request $request, string $kodeTps): Response|RedirectResponse
    {
        $tps = Tps::where('kode_tps', $kodeTps)->with('election.positions')->firstOrFail();

        $tokenId = $request->session()->get('bilik_token_id_' . $tps->id);

        if (! $tokenId) {
            return redirect()->route('bilik.masuk', $kodeTps)
                ->with('error', 'Sesi token tidak valid, silakan scan ulang.');
        }

        $positionIds = $tps->election->positions->pluck('id');

        $data = $request->validate([
            'pilihan' => ['required', 'array', 'size:' . $positionIds->count()],
            'pilihan.*.position_id' => ['required', 'integer', 'in:' . $positionIds->implode(',')],
            'pilihan.*.candidate_id' => ['required', 'integer', 'exists:candidates,id'],
        ]);

        try {
            [$batchCode, $struk] = DB::transaction(function () use ($tokenId, $tps, $data) {
                // lockForUpdate() mengunci baris token ini sampai transaksi selesai —
                // request lain yang coba pakai token yang sama harus menunggu di sini.
                $token = VoterToken::where('id', $tokenId)->lockForUpdate()->first();

                if (! $token || ! $token->isValid() || $token->tps_id !== $tps->id) {
                    throw new \RuntimeException('token_invalid');
                }

                $batchCode = 'BTC-' . Str::upper(Str::random(10));
                $struk = [];

                foreach ($data['pilihan'] as $item) {
                    $vote = Vote::create([
                        'position_id' => $item['position_id'],
                        'candidate_id' => $item['candidate_id'],
                        'tps_id' => $tps->id,
                        'token_hash' => $token->token_hash,
                        'waktu_vote' => now(),
                        'synced' => true,
                    ]);

                    $receipt = AuditReceipt::create([
                        'vote_id' => $vote->id,
                        'nomor_struk' => $batchCode . '-' . $item['position_id'],
                        'qr_verifikasi' => $batchCode,
                        'dicetak_pada' => now(),
                        'status_audit' => 'belum_dicek',
                    ]);

                    $struk[] = ['posisi_id' => $item['position_id'], 'nomor_struk' => $receipt->nomor_struk];
                }

                // Ditandai 'terpakai' SEBELUM transaksi commit — request kedua yang
                // menunggu lock akan melihat status ini begitu lock dilepas.
                $token->update(['status' => 'terpakai', 'dipakai_pada' => now()]);

                return [$batchCode, $struk];
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('bilik.masuk', $kodeTps)
                ->with('error', 'Token ini sudah dipakai atau tidak valid lagi.');
        }

        $request->session()->forget('bilik_token_id_' . $tps->id);
        $request->session()->regenerate(); // putus sesi lama setelah selesai vote

        return Inertia::render('Bilik/Receipt', [
            'tps' => $tps,
            'batchCode' => $batchCode,
            'struk' => $struk,
            'waktu' => now()->translatedFormat('d F Y H:i'),
        ]);
    }
}
