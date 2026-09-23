<?php

namespace App\Imports;

use App\Models\Tps;
use App\Models\Voter;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Format kolom file (heading row wajib ada, urutan bebas):
 * nik | nama | alamat | kode_tps (opsional)
 *
 * - nik akan di-hash (untuk cek duplikat) DAN dienkripsi (untuk disimpan) — tidak pernah
 *   disimpan dalam bentuk polos di kolom lain.
 * - kode_tps opsional: kalau diisi dan cocok dengan TPS yang sudah dibuat di pemilihan ini,
 *   voter langsung terikat ke TPS tersebut. Kalau kosong, admin/petugas assign belakangan.
 */
class VotersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use Importable, SkipsFailures;

    public function __construct(private int $electionId) {}
    public function model(array $row)
    {
        $nik = trim((string) $row['nik']);
        $nikHash = hash('sha256', $nik);

        // Lewati baris yang NIK-nya sudah terdaftar di pemilihan ini (hindari duplikat saat re-upload).
        $sudahAda = Voter::where('election_id', $this->electionId)
            ->where('nik_hash', $nikHash)
            ->exists();

        if ($sudahAda) {
            return null;
        }

        $tpsId = null;
        if (! empty($row['kode_tps'])) {
            $tpsId = Tps::where('election_id', $this->electionId)
                ->where('kode_tps', trim((string) $row['kode_tps']))
                ->value('id');
        }

        return new Voter([
            'election_id' => $this->electionId,
            'tps_id' => $tpsId,
            'nik_hash' => $nikHash,
            'nik_encrypted' => $nik, // otomatis dienkripsi oleh cast 'encrypted' di model Voter
            'nama' => trim((string) $row['nama']),
            'alamat' => $row['alamat'] ?? null,
            'status_verifikasi' => 'belum',
        ]);
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'digits:16'],
            'nama' => ['required', 'string', 'max:255'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nik.required' => 'Kolom NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nama.required' => 'Kolom nama wajib diisi.',
        ];
    }
}
