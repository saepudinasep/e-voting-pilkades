<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    public function index(Election $election): Response
    {
        return Inertia::render('Admin/Positions/Index', [
            'election' => $election,
            'positions' => $election->positions()
                ->withCount('candidates')
                ->orderBy('urutan')
                ->get(),
        ]);
    }

    public function store(Request $request, Election $election): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $position = $election->positions()->create($data);

        AuditLog::catat('tambah_posisi', 'positions', $position->id, null, $position->toArray());

        return back()->with('success', 'Posisi berhasil ditambahkan.');
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);

        $sebelum = $position->toArray();
        $position->update($data);

        AuditLog::catat('ubah_posisi', 'positions', $position->id, $sebelum, $position->toArray());

        return back()->with('success', 'Posisi berhasil diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->candidates()->exists()) {
            return back()->with('error', 'Hapus dulu semua kandidat di posisi ini.');
        }

        $sebelum = $position->toArray();
        $position->delete();

        AuditLog::catat('hapus_posisi', 'positions', $position->id, $sebelum, null);

        return back()->with('success', 'Posisi berhasil dihapus.');
    }
}
