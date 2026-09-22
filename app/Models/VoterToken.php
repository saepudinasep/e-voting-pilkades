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
     * Generate token acak, kembalikan versi PLAINTEXT untuk dicetak/ditampilkan sebagai QR,
     * sementara yang disimpan ke DB hanya HASH-nya. Voter/petugas tidak boleh melihat token_hash.
     */
    public static function generateFor(Voter $voter, Tps $tps, int $dibuatOleh, int $masaBerlakuMenit = 15): array
    {
        $plainToken = Str::random(32);

        $token = static::create([
            'voter_id' => $voter->id,
            'tps_id' => $tps->id,
            'token_hash' => Hash::make($plainToken),
            'status' => 'aktif',
            'dibuat_oleh' => $dibuatOleh,
            'kedaluwarsa_pada' => now()->addMinutes($masaBerlakuMenit),
        ]);

        return [
            'token_model' => $token,
            'plain_token' => $plainToken, // ini yang di-encode jadi QR untuk voter
        ];
    }

    public function isValid(): bool
    {
        return $this->status === 'aktif' && $this->kedaluwarsa_pada->isFuture();
    }
}
