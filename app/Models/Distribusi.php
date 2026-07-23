<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat distribusi Ketua Tim → Staff (relasi 1:1 per permohonan pada satu waktu).
 * Baris baru dibuat setiap kali terjadi reassignment (M-17) — jangan overwrite baris lama,
 * cukup set is_aktif = false pada baris lama & insert baris baru.
 * Ref: CLAUDE.md §3 poin 1, Tahap 4 §3.7
 */
class Distribusi extends Model
{
    use HasFactory;

    protected $table = 'distribusi';
    public const UPDATED_AT = null;

    public const JENIS_DISTRIBUSI_AWAL = 'distribusi_awal';
    public const JENIS_REASSIGNMENT = 'reassignment';

    protected $fillable = [
        'permohonan_id',
        'ketua_tim_id',
        'staff_id',
        'jenis',
        'is_aktif',
        'tanggal',
        'tanggal_reassign_terakhir',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
        'tanggal' => 'datetime',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function ketuaTim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_tim_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }
}
