<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Tps extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'tps';

    protected $fillable = [
        'election_id',
        'nama_lokasi',
        'kode_tps',
        'device_id',
        'status_koneksi',
        'terakhir_online',
    ];

    protected $casts = [
        'terakhir_online' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function voterTokens(): HasMany
    {
        return $this->hasMany(VoterToken::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    /**
     * Generate token device baru untuk TPS ini, sekaligus mencabut token lama
     * (1 TPS = 1 device aktif pada satu waktu — kalau perangkat diganti, generate ulang).
     */
    public function generateDeviceToken(): string
    {
        $this->tokens()->delete();

        return $this->createToken('device-' . $this->kode_tps)->plainTextToken;
    }
}
