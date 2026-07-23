<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
    ];

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
}
