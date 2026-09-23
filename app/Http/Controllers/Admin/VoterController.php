<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\VotersImport;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Voter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Excel;

class VoterController extends Controller
{
    public function index(Request $request, Election $election): Response
    {
        $status = $request->query('status');

        $voters = $election->voters()
            ->with('tps:id,nama_lokasi,kode_tps')
            ->when($status, fn($q) => $q->where('status_verifikasi', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Voters/Index', [
            'election' => $election,
            'voters' => $voters,
            'filterStatus' => $status,
            'ringkasan' => [
                'total' => $election->voters()->count(),
                'terverifikasi' => $election->voters()->where('status_verifikasi', 'terverifikasi')->count(),
                'belum' => $election->voters()->where('status_verifikasi', 'belum')->count(),
                'ditolak' => $election->voters()->where('status_verifikasi', 'ditolak')->count(),
            ],
            'tpsOptions' => $election->tpsList()->get(['id', 'nama_lokasi', 'kode_tps']),
        ]);
    }

    public function import(Request $request, Election $election): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ]);

        $import = new VotersImport($election->id);
        Excel::import($import, $request->file('file'));

        $jumlahGagal = $import->failures()->count();
        $pesan = 'Import DPT selesai.';
        if ($jumlahGagal > 0) {
            $pesan .= " {$jumlahGagal} baris dilewati karena tidak valid (lihat format NIK/nama).";
        }

        AuditLog::catat('import_dpt', 'voters', null, null, [
            'election_id' => $election->id,
            'baris_gagal' => $jumlahGagal,
        ]);

        return back()->with($jumlahGagal > 0 ? 'warning' : 'success', $pesan);
    }

    public function verify(Voter $voter): RedirectResponse
    {
        if ($voter->status_verifikasi === 'terverifikasi') {
            return back()->with('error', 'Pemilih ini sudah terverifikasi.');
        }

        $sebelum = $voter->toArray();

        $voter->update([
            'status_verifikasi' => 'terverifikasi',
            'diverifikasi_pada' => now(),
            'diverifikasi_oleh' => Auth::id(),
        ]);

        AuditLog::catat('verifikasi_voter', 'voters', $voter->id, $sebelum, $voter->toArray());

        return back()->with('success', "{$voter->nama} berhasil diverifikasi.");
    }

    public function reject(Request $request, Voter $voter): RedirectResponse
    {
        $request->validate([
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        $sebelum = $voter->toArray();

        $voter->update([
            'status_verifikasi' => 'ditolak',
            'diverifikasi_pada' => now(),
            'diverifikasi_oleh' => Auth::id(),
        ]);

        AuditLog::catat('tolak_voter', 'voters', $voter->id, $sebelum, $voter->toArray() + [
            'alasan' => $request->input('alasan'),
        ]);

        return back()->with('success', "{$voter->nama} ditandai ditolak.");
    }

    public function assignTps(Request $request, Voter $voter): RedirectResponse
    {
        $data = $request->validate([
            'tps_id' => ['required', 'exists:tps,id'],
        ]);

        $sebelum = $voter->toArray();
        $voter->update($data);

        AuditLog::catat('assign_tps_voter', 'voters', $voter->id, $sebelum, $voter->toArray());

        return back()->with('success', 'TPS pemilih berhasil diperbarui.');
    }

    public function destroy(Voter $voter): RedirectResponse
    {
        if ($voter->status_verifikasi === 'terverifikasi' && $voter->tokens()->exists()) {
            return back()->with('error', 'Pemilih ini sudah punya token aktif, tidak bisa dihapus.');
        }

        $sebelum = $voter->toArray();
        $voter->delete();

        AuditLog::catat('hapus_voter', 'voters', $voter->id, $sebelum, null);

        return back()->with('success', 'Data pemilih dihapus.');
    }
}
