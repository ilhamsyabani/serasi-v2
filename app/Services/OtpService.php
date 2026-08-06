<?php

namespace App\Services;

use App\Models\OtpLog;
use App\Models\Pbf;
use App\Models\PasswordResetOtp;
use App\Mail\OtpMail;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    private const LIFETIME_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public static function generatePassword(): string
    {
        return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 12);
    }

    public function buatDanKirimOtp(Pbf $pbf, string $channel = 'whatsapp'): OtpLog
    {
        // Expire any pending OTP for this pbf first
        OtpLog::where('pbf_id', $pbf->id)
            ->where('status', OtpLog::STATUS_TERKIRIM)
            ->where('expires_at', '>', now())
            ->update(['status' => OtpLog::STATUS_KEDALUWARSA]);

        $kode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::LIFETIME_MINUTES);
        $hash = hash('sha256', $kode . $pbf->email . $expiresAt->timestamp);

        $log = OtpLog::create([
            'pbf_id'    => $pbf->id,
            'kode_otp'  => $hash,
            'channel'   => $channel,
            'expires_at'=> $expiresAt,
            'attempts'  => 0,
            'status'    => OtpLog::STATUS_TERKIRIM,
        ]);

        if ($channel === OtpLog::CHANNEL_WHATSAPP) {
            app(WhatsappSender::class)->send(
                $pbf->no_whatsapp,
                "🔐 *Kode OTP Login*\n\nKode verifikasi Anda: *$kode*\nBerlaku selama *10 menit*.\n\nJangan berikan kode ini kepada siapapun."
            );
        } else {
            try {
                Mail::to($pbf->email)->send(new OtpMail($kode));
            } catch (\Throwable $e) {
                Log::warning('Gagal kirim OTP email', [
                    'email' => $pbf->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $log;
    }

    public function verifikasiOtp(Pbf $pbf, string $kode): bool
    {
        $log = OtpLog::where('pbf_id', $pbf->id)
            ->where('status', OtpLog::STATUS_TERKIRIM)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if (! $log) {
            return false;
        }

        // Increment attempts even on failure to track brute-force attempts
        $log->increment('attempts');

        if ($log->attempts > self::MAX_ATTEMPTS) {
            $log->update(['status' => OtpLog::STATUS_KEDALUWARSA]);
            return false;
        }

        $hash = hash('sha256', $kode . $pbf->email . $log->expires_at->timestamp);
        if (! hash_equals($log->kode_otp, $hash)) {
            return false;
        }

        $log->update([
            'status'     => OtpLog::STATUS_TERVERIFIKASI,
            'verified_at'=> now(),
        ]);

        $pbf->update(['otp_terverifikasi' => true]);

        return true;
    }

    public function terlaluBanyakAttempt(Pbf $pbf): bool
    {
        return OtpLog::where('pbf_id', $pbf->id)
            ->where('status', OtpLog::STATUS_TERKIRIM)
            ->where('expires_at', '>', now())
            ->where('attempts', '>=', self::MAX_ATTEMPTS)
            ->exists();
    }

    public static function generateAndSendPasswordResetLink(Pbf $pbf): PasswordResetOtp
    {
        PasswordResetOtp::where('email', $pbf->email)
            ->where('status', PasswordResetOtp::STATUS_TERKIRIM)
            ->where('expires_at', '>', now())
            ->update(['status' => PasswordResetOtp::STATUS_KEDALUWARSA]);

        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addMinutes(PasswordResetOtp::LIFETIME_MINUTES);

        $reset = PasswordResetOtp::create([
            'email' => $pbf->email,
            'token' => $token,
            'kode_otp' => null,
            'channel' => OtpLog::CHANNEL_WHATSAPP,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'status' => PasswordResetOtp::STATUS_TERKIRIM,
        ]);

        $resetUrl = route('pemohon.password.reset', ['token' => $token]);

        if ($pbf->no_whatsapp) {
            try {
                app(WhatsappSender::class)->send(
                    $pbf->no_whatsapp,
                    "🔑 *Reset Password — Portal Pemohon*\n\n" .
                    "Klik tautan berikut untuk mereset password:\n$resetUrl\n\n" .
                    "Tautan berlaku selama *30 menit*.\n\n" .
                    "Jangan berikan tautan ini kepada siapapun."
                );
            } catch (\Throwable $e) {
                Log::warning('Gagal kirim WA reset password', [
                    'no_whatsapp' => $pbf->no_whatsapp,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($pbf->email) {
            try {
                Mail::to($pbf->email)->send(new PasswordResetMail($resetUrl));
            } catch (\Throwable $e) {
                Log::warning('Gagal kirim email reset password', [
                    'email' => $pbf->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $reset;
    }
}
