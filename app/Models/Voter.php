<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'tps_id',
        'nik_hash',
        'nik_encrypted',
        'nama',
        'alamat',
        'status_verifikasi',
        'diverifikasi_pada',
        'diverifikasi_oleh',
    ];

    protected $casts = [
        'nik_encrypted' => 'encrypted', // otomatis dienkripsi/dekripsi oleh Laravel
        'diverifikasi_pada' => 'datetime',
    ];

    protected $hidden = ['nik_encrypted'];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function tps(): BelongsTo
    {
        return $this->belongsTo(Tps::class, 'tps_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(VoterToken::class);
    }

    public function isTerverifikasi(): bool
    {
        return $this->status_verifikasi === 'terverifikasi';
    }
}
