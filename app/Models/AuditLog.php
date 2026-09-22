<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'aksi',
        'tabel_terkait',
        'record_id',
        'data_sebelum',
        'data_sesudah',
        'ip_address',
        'waktu',
    ];

    protected $casts = [
        'data_sebelum' => 'array',
        'data_sesudah' => 'array',
        'waktu' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(string $aksi, ?string $tabel = null, ?int $recordId = null, ?array $sebelum = null, ?array $sesudah = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'aksi' => $aksi,
            'tabel_terkait' => $tabel,
            'record_id' => $recordId,
            'data_sebelum' => $sebelum,
            'data_sesudah' => $sesudah,
            'ip_address' => request()->ip(),
            'waktu' => now(),
        ]);
    }
}
