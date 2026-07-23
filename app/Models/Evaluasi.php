<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Siklus evaluasi dokumen oleh Staff. siklus_ke: 0 = evaluasi awal, 1-3 = pasca revisi.
 *
 * Form disederhanakan (revisi terbaru, lihat CLAUDE.md §3 poin 12):
 * - Lengkap → tidak perlu isian tambahan.
 * - Tidak Lengkap → cukup kolom `catatan`. TIDAK ADA field narasi/dokumen evaluasi —
 *   jangan tambahkan lagi field ini, sudah dihapus dari desain.
 *
 * Ref: Tahap 4 §3.8
 */
class Evaluasi extends Model
{
    use HasFactory;

    protected $table = 'evaluasi';
    public const UPDATED_AT = null;

    public const HASIL_LENGKAP = 'lengkap';
    public const HASIL_TIDAK_LENGKAP = 'tidak_lengkap';

    protected $fillable = [
        'permohonan_id',
        'staff_id',
        'siklus_ke',
        'hasil',
        'catatan',
        'tanggal_evaluasi',
    ];

    protected $casts = [
        'siklus_ke' => 'integer',
        'tanggal_evaluasi' => 'datetime',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /** Hanya ada jika hasil = tidak_lengkap. */
    public function revisi(): HasOne
    {
        return $this->hasOne(Revisi::class);
    }

    public function isLengkap(): bool
    {
        return $this->hasil === self::HASIL_LENGKAP;
    }

    public function isSiklusTerakhir(): bool
    {
        return $this->siklus_ke >= 3;
    }
}
