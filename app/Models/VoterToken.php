<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VoterToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'voter_id',
        'tps_id',
        'token_hash',
        'status',
        'dibuat_oleh',
        'kedaluwarsa_pada',
        'dipakai_pada',
    ];

    protected $casts = [
        'kedaluwarsa_pada' => 'datetime',
        'dipakai_pada' => 'datetime',
    ];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function tps(): BelongsTo
    {
        return $this->belongsTo(Tps::class, 'tps_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Generate token acak, kembalikan versi PLAINTEXT untuk di-encode jadi QR,
     * sementara yang disimpan ke DB adalah hash SHA-256-nya.
     *
     * Catatan perubahan dari versi Tahap 1: sebelumnya pakai Hash::make() (bcrypt),
     * yang cocok untuk PASSWORD (dibandingkan satu-per-satu via Hash::check), tapi
     * TIDAK cocok untuk TOKEN yang perlu di-lookup langsung dari QR yang di-scan
     * (butuh WHERE token_hash = ... , bukan looping cek semua token aktif).
     * Karena token ini string acak 32 karakter (entropi tinggi, tidak bisa ditebak),
     * hash satu-arah tanpa salt (sha256) tetap aman dipakai sebagai kunci lookup —
     * beda kasus dengan hashing password yang butuh salt karena manusia sering
     * pakai kombinasi yang mudah ditebak.
     */
    public static function generateFor(Voter $voter, Tps $tps, int $dibuatOleh, int $masaBerlakuMenit = 15): array
    {
        $plainToken = Str::random(32);

        $token = static::create([
            'voter_id' => $voter->id,
            'tps_id' => $tps->id,
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'aktif',
            'dibuat_oleh' => $dibuatOleh,
            'kedaluwarsa_pada' => now()->addMinutes($masaBerlakuMenit),
        ]);

        return [
            'token_model' => $token,
            'plain_token' => $plainToken, // ini yang di-encode jadi QR untuk voter
        ];
    }

    /** Cari token aktif & belum kedaluwarsa dari plaintext token hasil scan QR. */
    public static function findValid(string $plainToken, int $tpsId): ?self
    {
        return static::where('tps_id', $tpsId)
            ->where('token_hash', hash('sha256', $plainToken))
            ->where('status', 'aktif')
            ->where('kedaluwarsa_pada', '>', now())
            ->first();
    }

    public function isValid(): bool
    {
        return $this->status === 'aktif' && $this->kedaluwarsa_pada->isFuture();
    }
}
