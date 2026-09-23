<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ElectionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Elections/Index', [
            'elections' => Election::withCount(['positions', 'voters', 'tpsList'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Elections/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'wilayah' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $election = Election::create($data + ['status' => 'draft']);

        AuditLog::catat('buat_pemilihan', 'elections', $election->id, null, $election->toArray());

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan berhasil dibuat.');
    }

    public function edit(Election $election): Response
    {
        return Inertia::render('Admin/Elections/Edit', [
            'election' => $election,
        ]);
    }

    public function update(Request $request, Election $election): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'wilayah' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,berjalan,selesai'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $sebelum = $election->toArray();
        $election->update($data);

        AuditLog::catat('ubah_pemilihan', 'elections', $election->id, $sebelum, $election->toArray());

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan berhasil diperbarui.');
    }

    public function destroy(Election $election): RedirectResponse
    {
        if ($election->status === 'berjalan') {
            return back()->with('error', 'Tidak bisa menghapus pemilihan yang sedang berjalan.');
        }

        $sebelum = $election->toArray();
        $election->delete();

        AuditLog::catat('hapus_pemilihan', 'elections', $election->id, $sebelum, null);

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan berhasil dihapus.');
    }
}
