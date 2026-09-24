<?php

namespace App\Http\Controllers\Tps;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Voter;
use App\Models\VoterToken;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckinController extends Controller
{
    public function index(Request $request): Response
    {
        $tps = $request->user()->tps()->with('election')->firstOrFail();

        $voter = null;
        $error = null;
        $nik = $request->query('nik');

        if ($nik) {
            if (! preg_match('/^\d{16}$/', $nik)) {
                $error = 'NIK harus 16 digit angka.';
            } else {
                $voter = Voter::where('election_id', $tps->election_id)
                    ->where('nik_hash', hash('sha256', $nik))
                    ->with('tps:id,nama_lokasi')
                    ->first();

                if (! $voter) {
                    $error = 'NIK tidak ditemukan di DPT pemilihan ini.';
                } elseif ($voter->tps_id && $voter->tps_id !== $tps->id) {
                    $error = "Pemilih ini terdaftar di TPS lain ({$voter->tps->nama_lokasi}).";
                    $voter = null;
                }
            }
        }

        return Inertia::render('Tps/Checkin', [
            'tps' => $tps,
            'voter' => $voter,
            'nikDicari' => $nik,
            'error' => $error,
        ]);
    }

    public function generateToken(Request $request, Voter $voter): Response
    {
        $tps = $request->user()->tps()->with('election')->firstOrFail();

        abort_unless($voter->election_id === $tps->election_id, 403, 'Pemilih bukan dari pemilihan TPS ini.');
        abort_if($voter->tps_id && $voter->tps_id !== $tps->id, 403, 'Pemilih terdaftar di TPS lain.');
        abort_if($voter->status_verifikasi === 'ditolak', 422, 'Pemilih ini statusnya ditolak, tidak bisa diberi token.');

        // Non-aktifkan token lama yang mungkin masih tergantung (mis. voter sempat batal & kembali lagi)
        $voter->tokens()->where('status', 'aktif')->update(['status' => 'kedaluwarsa']);

        $sebelumVoter = $voter->toArray();

        if ($voter->status_verifikasi !== 'terverifikasi' || ! $voter->tps_id) {
            $voter->update([
                'status_verifikasi' => 'terverifikasi',
                'tps_id' => $voter->tps_id ?? $tps->id,
                'diverifikasi_pada' => $voter->diverifikasi_pada ?? now(),
                'diverifikasi_oleh' => $voter->diverifikasi_oleh ?? $request->user()->id,
            ]);

            AuditLog::catat('checkin_verifikasi_voter', 'voters', $voter->id, $sebelumVoter, $voter->toArray());
        }

        $hasil = VoterToken::generateFor($voter, $tps, $request->user()->id);

        AuditLog::catat('buat_token', 'voter_tokens', $hasil['token_model']->id, null, [
            'voter_id' => $voter->id,
            'tps_id' => $tps->id,
        ]);

        return Inertia::render('Tps/Checkin', [
            'tps' => $tps,
            'voter' => $voter->fresh(),
            'nikDicari' => null,
            'error' => null,
            'tokenBaru' => [
                'plain' => $hasil['plain_token'],
                'kedaluwarsa' => $hasil['token_model']->kedaluwarsa_pada,
            ],
        ]);
    }
}
