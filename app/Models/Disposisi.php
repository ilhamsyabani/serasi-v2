<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Disposisi Kepala Balai → Ketua Tim. Satu permohonan hanya 1 disposisi
 * (Asumsi A-01, Tahap 1) — kolom permohonan_id di migration harus UNIQUE.
 * Ref: Tahap 4 §3.6
 */
class Disposisi extends Model
{
    use HasFactory;

    protected $table = 'disposisi';
    public const UPDATED_AT = null;

    protected $fillable = [
        'permohonan_id',
        'kepala_balai_id',
        'ketua_tim_id',
        'catatan',
        'tanggal_disposisi',
    ];

    protected $casts = [
        'tanggal_disposisi' => 'datetime',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function kepalaBalai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_balai_id');
    }

    public function ketuaTim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_tim_id');
    }
}
