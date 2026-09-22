<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'vote_id',
        'nomor_struk',
        'qr_verifikasi',
        'dicetak_pada',
        'status_audit',
    ];

    protected $casts = [
        'dicetak_pada' => 'datetime',
    ];

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }
}
