<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat status permohonan — basis timeline & perhitungan SLA.
 * `is_clock_off` = true khusus status Revisi ke-1/2/3: durasi TIDAK dihitung
 * sebagai keterlambatan staff. `durasi_hari_kerja` dihitung backend memakai
 * SlaConfig & HariLibur saat `waktu_selesai` terisi.
 * Ref: CLAUDE.md §3 poin 3 & §4, Tahap 4 §3.12
 */
class StatusLog extends Model
{
    use HasFactory;

    protected $table = 'status_log';
    public const UPDATED_AT = null;

    protected $fillable = [
        'permohonan_id',
        'status',
        'waktu_mulai',
        'waktu_selesai',
        'is_clock_off',
        'durasi_hari_kerja',
    ];

    protected $casts = [
        'waktu_mulai' => 'datetime',
        'waktu_selesai' => 'datetime',
        'is_clock_off' => 'boolean',
        'durasi_hari_kerja' => 'integer',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    /** Baris status yang sedang berjalan (belum ada waktu_selesai). */
    public function scopeSedangBerjalan(Builder $query): Builder
    {
        return $query->whereNull('waktu_selesai');
    }

    public function scopeBukanClockOff(Builder $query): Builder
    {
        return $query->where('is_clock_off', false);
    }
}
