<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'position_id',
        'candidate_id',
        'tps_id',
        'token_hash',
        'waktu_vote',
        'synced',
        'synced_at',
    ];

    protected $casts = [
        'waktu_vote' => 'datetime',
        'synced' => 'boolean',
        'synced_at' => 'datetime',
    ];

    // Sengaja tidak ada relasi ke Voter/VoterToken di sini — jaga anonimitas suara di level model juga.

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function tps(): BelongsTo
    {
        return $this->belongsTo(Tps::class, 'tps_id');
    }

    public function auditReceipt(): HasOne
    {
        return $this->hasOne(AuditReceipt::class);
    }
}
