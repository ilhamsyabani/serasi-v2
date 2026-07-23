<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun Pelaku Usaha (PBF) — dipakai sebagai master data sekaligus akun login.
 * Login: Email/No. WhatsApp + Password, OTP hanya saat pertama kali login.
 * Kredensial awal dikirim otomatis via WA/Email saat pengajuan pertama diinput Kepala Balai.
 *
 * CATATAN DESAIN (belum final, lihat DESIGN.md §7 poin 1):
 * Asumsi saat ini 1 NIB = 1 akun login. Jika ke depan satu PBF butuh banyak
 * PIC/kontak dengan login terpisah, tabel ini perlu dipecah menjadi
 * `pbf` (master data murni) + `pemohon_akun` (kredensial, 1:N).
 *
 * Ref: DESIGN.md §2, Tahap 4 §3.3
 */
class Pbf extends Authenticatable
{
    use HasFactory;

    protected $table = 'pbf';

    protected $fillable = [
        'nib',
        'nama_pbf',
        'email',
        'no_whatsapp',
        'password_hash',
        'otp_terverifikasi',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'otp_terverifikasi' => 'boolean',
    ];

    /**
     * Laravel Auth mengharapkan kolom `password`; di skema kita namanya
     * `password_hash`, jadi di-override di sini agar guard `pemohon` tetap jalan.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class);
    }

    public function otpLogs(): HasMany
    {
        return $this->hasMany(OtpLog::class);
    }
}
