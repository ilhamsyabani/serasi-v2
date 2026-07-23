<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aksi Ketua Tim via modul M-17 (Eskalasi & Reassignment): reassign staff,
 * kirim reminder, atau aksi lain untuk optimasi proses.
 * Ref: DESIGN.md §4 (M-17), Tahap 4 §3.14
 */
class ReassignmentLog extends Model
{
    use HasFactory;

    protected $table = 'reassignment_log';
    public const UPDATED_AT = null;

    public const JENIS_REASSIGN = 'reassign';
    public const JENIS_REMINDER = 'reminder';
    public const JENIS_LAINNYA = 'lainnya';

    protected $fillable = [
        'permohonan_id',
        'ketua_tim_id',
        'staff_lama_id',
        'staff_baru_id',
        'jenis_aksi',
        'alasan',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function ketuaTim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_tim_id');
    }

    public function staffLama(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_lama_id');
    }

    public function staffBaru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_baru_id');
    }
}
