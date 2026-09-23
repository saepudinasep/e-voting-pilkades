<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CandidateController extends Controller
{
    public function index(Position $position): Response
    {
        return Inertia::render('Admin/Candidates/Index', [
            'position' => $position->load('election'),
            'candidates' => $position->candidates()->orderBy('nomor_urut')->get(),
        ]);
    }

    public function store(Request $request, Position $position): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nomor_urut' => ['required', 'integer', 'min:1'],
            'visi_misi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('kandidat', 'public');
        }

        $candidate = $position->candidates()->create($data);

        AuditLog::catat('tambah_kandidat', 'candidates', $candidate->id, null, $candidate->toArray());

        return back()->with('success', 'Kandidat berhasil ditambahkan.');
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nomor_urut' => ['required', 'integer', 'min:1'],
            'visi_misi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            if ($candidate->foto) {
                Storage::disk('public')->delete($candidate->foto);
            }
            $data['foto'] = $request->file('foto')->store('kandidat', 'public');
        }

        $sebelum = $candidate->toArray();
        $candidate->update($data);

        AuditLog::catat('ubah_kandidat', 'candidates', $candidate->id, $sebelum, $candidate->toArray());

        return back()->with('success', 'Kandidat berhasil diperbarui.');
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        if ($candidate->votes()->exists()) {
            return back()->with('error', 'Kandidat ini sudah punya suara masuk, tidak bisa dihapus.');
        }

        if ($candidate->foto) {
            Storage::disk('public')->delete($candidate->foto);
        }

        $sebelum = $candidate->toArray();
        $candidate->delete();

        AuditLog::catat('hapus_kandidat', 'candidates', $candidate->id, $sebelum, null);

        return back()->with('success', 'Kandidat berhasil dihapus.');
    }
}
