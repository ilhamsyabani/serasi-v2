<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Log aktivitas lintas modul (M-11) — wajib diisi di setiap aksi penting:
 * disposisi, distribusi/reassign, evaluasi, upload surat, dsb (lihat CLAUDE.md §5).
 * `user_id` BUKAN foreign key polymorphic standar Laravel — merujuk ke
 * `users.id` atau `pbf.id` tergantung `user_type`, sama seperti Notifikasi.
 * Ref: Tahap 4 §3.18
 */
class AuditTrail extends Model
{
    use HasFactory;

    protected $table = 'audit_trail';
    public const UPDATED_AT = null;

    public const USER_TYPE_INTERNAL = 'internal';
    public const USER_TYPE_PEMOHON = 'pemohon';

    protected $fillable = [
        'user_id',
        'user_type',
        'aksi',
        'modul',
        'permohonan_id',
        'detail',
        'ip_address',
    ];

    protected $casts = [
        'detail' => 'array',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    /** Resolve pelaku aktual: User (internal) atau Pbf (pemohon). */
    public function user(): User|Pbf|null
    {
        if ($this->user_type === self::USER_TYPE_PEMOHON) {
            return Pbf::find($this->user_id);
        }

        return User::find($this->user_id);
    }
}
