<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tps;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PetugasController extends Controller
{
    public function index(): Response
    {
        $petugas = User::where('role', 'petugas_tps')
            ->with('tps:id,nama_lokasi,kode_tps,election_id')
            ->latest()
            ->get();

        // Daftar TPS lintas pemilihan, dikasih label pemilihan biar tidak ambigu
        // kalau ada beberapa pemilihan aktif sekaligus.
        $tpsOptions = Tps::with('election:id,nama')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'label' => "{$t->nama_lokasi} ({$t->kode_tps}) — {$t->election->nama}",
            ]);

        return Inertia::render('Admin/Petugas/Index', [
            'petugas' => $petugas,
            'tpsOptions' => $tpsOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'tps_id' => ['nullable', 'exists:tps,id'],
        ]);

        $petugas = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // otomatis di-hash lewat cast 'hashed'
            'role' => 'petugas_tps',
            'tps_id' => $data['tps_id'] ?? null,
            'email_verified_at' => now(), // langsung aktif, tidak perlu verifikasi email manual
        ]);

        AuditLog::catat('tambah_petugas', 'users', $petugas->id, null, $petugas->only(['id', 'name', 'email', 'tps_id']));

        return back()->with('success', "Petugas {$petugas->name} berhasil dibuat.");
    }

    public function update(Request $request, User $petugas): RedirectResponse
    {
        abort_unless($petugas->role === 'petugas_tps', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($petugas->id)],
            'password' => ['nullable', Password::min(8)],
            'tps_id' => ['nullable', 'exists:tps,id'],
        ]);

        $sebelum = $petugas->only(['id', 'name', 'email', 'tps_id']);

        $petugas->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'tps_id' => $data['tps_id'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $petugas->password = $data['password']; // otomatis di-hash
        }

        $petugas->save();

        AuditLog::catat('ubah_petugas', 'users', $petugas->id, $sebelum, $petugas->only(['id', 'name', 'email', 'tps_id']));

        return back()->with('success', "Data {$petugas->name} berhasil diperbarui.");
    }

    public function destroy(User $petugas): RedirectResponse
    {
        abort_unless($petugas->role === 'petugas_tps', 404);

        if ($petugas->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $sebelum = $petugas->only(['id', 'name', 'email', 'tps_id']);
        $namaTerhapus = $petugas->name;
        $petugas->delete();

        AuditLog::catat('hapus_petugas', 'users', $petugas->id, $sebelum, null);

        return back()->with('success', "Petugas {$namaTerhapus} berhasil dihapus.");
    }
}
