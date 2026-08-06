<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetOtp extends Model
{
    public const STATUS_TERKIRIM = 'terkirim';
    public const STATUS_TERVERIFIKASI = 'terverifikasi';
    public const STATUS_GAGAL = 'gagal';
    public const STATUS_KEDALUWARSA = 'kedaluwarsa';
    public const MAX_ATTEMPTS = 5;
    public const LIFETIME_MINUTES = 30;

    protected $table = 'password_reset_otp';

    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'email',
        'token',
        'kode_otp',
        'channel',
        'expires_at',
        'attempts',
        'status',
    ];

    protected $hidden = [
        'kode_otp',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function pbf(): BelongsTo
    {
        return $this->belongsTo(Pbf::class, 'email', 'email');
    }
}
