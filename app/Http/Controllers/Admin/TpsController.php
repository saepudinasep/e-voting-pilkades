<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Tps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TpsController extends Controller
{
    public function index(Election $election): Response
    {
        return Inertia::render('Admin/Tps/Index', [
            'election' => $election,
            'tpsList' => $election->tpsList()->withCount('voters')->get(),
        ]);
    }

    public function store(Request $request, Election $election): RedirectResponse
    {
        $data = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:255'],
        ]);

        $tps = $election->tpsList()->create([
            'nama_lokasi' => $data['nama_lokasi'],
            'kode_tps' => 'TPS-' . Str::upper(Str::random(6)),
            'status_koneksi' => 'offline',
        ]);

        AuditLog::catat('tambah_tps', 'tps', $tps->id, null, $tps->toArray());

        return back()->with('success', "TPS berhasil ditambahkan dengan kode {$tps->kode_tps}.");
    }

    public function update(Request $request, Tps $tpsLokasi): RedirectResponse
    {
        $data = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:255'],
        ]);

        $sebelum = $tpsLokasi->toArray();
        $tpsLokasi->update($data);

        AuditLog::catat('ubah_tps', 'tps', $tpsLokasi->id, $sebelum, $tpsLokasi->toArray());

        return back()->with('success', 'TPS berhasil diperbarui.');
    }

    public function destroy(Tps $tpsLokasi): RedirectResponse
    {
        if ($tpsLokasi->votes()->exists()) {
            return back()->with('error', 'TPS ini sudah punya data suara, tidak bisa dihapus.');
        }

        $sebelum = $tpsLokasi->toArray();
        $tpsLokasi->delete();

        AuditLog::catat('hapus_tps', 'tps', $tpsLokasi->id, $sebelum, null);

        return back()->with('success', 'TPS berhasil dihapus.');
    }
}
