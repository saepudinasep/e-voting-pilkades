<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Tps extends Model implements AuthenticatableContract
{
    use HasFactory, HasApiTokens, Authenticatable;

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

    public function generateDeviceToken(): string
    {
        $this->tokens()->delete();

        return $this->createToken('device-' . $this->kode_tps)->plainTextToken;
    }
}
