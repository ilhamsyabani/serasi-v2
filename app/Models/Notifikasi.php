<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log notifikasi Email/WhatsApp. Sejak revisi terakhir, penerima BUKAN cuma
 * Pemohon — Staff & Ketua Tim juga menerima notifikasi WA di tahap tertentu
 * (distribusi baru, revisi masuk, revisi selesai diupload, siap terbit,
 * reassignment, reminder manual). Kepala Balai tidak termasuk penerima rutin.
 * Lihat pemetaan lengkap di Tahap 2 §1.3 dan CLAUDE.md §3 poin 13.
 *
 * `tujuan_id` BUKAN foreign key polymorphic standar Laravel (morphTo) — merujuk
 * ke `pbf.id` atau `users.id` tergantung `tujuan_tipe`. Method tujuan() di bawah
 * meng-handle ini secara manual.
 *
 * Ref: Tahap 4 §3.13
 */
class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';
    public const UPDATED_AT = null;

    public const TUJUAN_PEMOHON = 'pemohon';
    public const TUJUAN_STAFF = 'staff';
    public const TUJUAN_KETUA_TIM = 'ketua_tim';
    public const TUJUAN_KEPALA_BALAI = 'kepala_balai';

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_TERKIRIM = 'terkirim';
    public const STATUS_GAGAL = 'gagal';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'permohonan_id',
        'tujuan_tipe',
        'tujuan_id',
        'channel',
        'template_kode',
        'status_kirim',
        'retry_count',
        'sent_at',
        'error_message',
        'dibaca_at',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
        'dibaca_at' => 'datetime',
    ];

    // ── Relasi ─────────────────────────────────────────

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    /**
     * Resolve penerima aktual: Pbf jika tujuan_tipe = pemohon,
     * User (Kepala Balai/Ketua Tim/Staff) untuk tipe lainnya.
     */
    public function tujuan(): Pbf|User|null
    {
        if ($this->tujuan_tipe === self::TUJUAN_PEMOHON) {
            return Pbf::find($this->tujuan_id);
        }

        return User::find($this->tujuan_id);
    }

    // ── Scope ──────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('dibaca_at');
    }

    // ── Helper ─────────────────────────────────────────

    public function isUnread(): bool
    {
        return $this->dibaca_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->dibaca_at === null) {
            $this->update(['dibaca_at' => now()]);
        }
    }

    public function getLabelAttribute(): string
    {
        return self::TEMPLATE_LABELS[$this->template_kode] ?? str_replace('_', ' ', ucfirst($this->template_kode ?? 'Notifikasi'));
    }

    public function getIconAttribute(): string
    {
        return self::TEMPLATE_ICONS[$this->template_kode] ?? 'ph-bell';
    }

    public function getChannelBadgeClassAttribute(): string
    {
        return match ($this->channel) {
            self::CHANNEL_WHATSAPP => 'bg-green-100 text-green-700',
            self::CHANNEL_EMAIL => 'bg-blue-100 text-blue-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    // ── Statis ─────────────────────────────────────────

    /** Label ramah-baca untuk tiap template kode. */
    public const TEMPLATE_LABELS = [
        'PENGAJUAN_BARU' => 'Pengajuan Baru',
        'DISPOSISI' => 'Disposisi Permohonan',
        'DISTRIBUSI' => 'Distribusi ke Staff',
        'EVALUASI_LENGKAP' => 'Evaluasi Lengkap',
        'EVALUASI_TIDAK_LENGKAP' => 'Evaluasi Tidak Lengkap',
        'REVISI' => 'Permintaan Revisi',
        'REVISI_UPLOADED' => 'Revisi Diunggah Pemohon',
        'SIAP_TERBIT' => 'Siap Terbit Surat',
        'SURAT_TERBIT' => 'Surat Pengesahan Terbit',
        'REMINDER' => 'Pengingat',
        'AKUN_BARU' => 'Akun Baru',
        'PENGAJUAN_ULANG' => 'Pengajuan Ulang',
        'REASSIGNMENT' => 'Penugasan Ulang Staff',
    ];

    /** Phosphor icon name untuk tiap template kode. */
    public const TEMPLATE_ICONS = [
        'PENGAJUAN_BARU' => 'ph-file-plus',
        'DISPOSISI' => 'ph-arrow-bend-up-right',
        'DISTRIBUSI' => 'ph-user-plus',
        'EVALUASI_LENGKAP' => 'ph-check-circle',
        'EVALUASI_TIDAK_LENGKAP' => 'ph-x-circle',
        'REVISI' => 'ph-note-pencil',
        'REVISI_UPLOADED' => 'ph-upload-simple',
        'SIAP_TERBIT' => 'ph-seal-check',
        'SURAT_TERBIT' => 'ph-seal-check-fill',
        'REMINDER' => 'ph-bell-ringing',
        'AKUN_BARU' => 'ph-user-circle-plus',
        'PENGAJUAN_ULANG' => 'ph-arrows-clockwise',
        'REASSIGNMENT' => 'ph-arrows-left-right',
    ];
}
