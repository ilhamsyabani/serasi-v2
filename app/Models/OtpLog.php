<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat OTP pemohon — dipakai hanya saat login pertama kali (Tahap 1/3).
 * Simpan `kode_otp` dalam bentuk hash, bukan plaintext.
 * Ref: Tahap 4 §3.15
 */
class OtpLog extends Model
{
    use HasFactory;

    protected $table = 'otp_log';
    public const UPDATED_AT = null;

    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_TERKIRIM = 'terkirim';
    public const STATUS_TERVERIFIKASI = 'terverifikasi';
    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    protected $fillable = [
        'pbf_id',
        'kode_otp',
        'channel',
        'expires_at',
        'attempts',
        'status',
        'verified_at',
    ];

    protected $hidden = [
        'kode_otp',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at'  => 'datetime',
    ];

    public function pbf(): BelongsTo
    {
        return $this->belongsTo(Pbf::class);
    }
}
