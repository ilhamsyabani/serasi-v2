<?php

namespace App\Services;

use App\Models\Pbf;
use App\Models\OtpLog;
use App\Services\WhatsappSender;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class OtpService
{
    public function __construct(private WhatsappSender $wa) {}

    public static function generatePassword(): string
    {
        return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 12);
    }

    public function buatDanKirimOtp(Pbf $pbf, string $channel = 'email'): OtpLog
    {
        $kode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = hash('sha256', $kode . $pbf->email . time());

        $log = OtpLog::create([
            'pbf_id' => $pbf->id,
            'kode_otp' => $hash,
            'channel' => $channel,
            'status' => 'terkirim',
            'created_at' => now(),
        ]);

        if ($channel === 'whatsapp') {
            $this->wa->send($pbf->no_whatsapp, "Kode OTP Anda: $kode (berlalu 10 menit)");
        } else {
            Mail::to($pbf->email)->send(new OtpMail($kode));
        }

        return $log;
    }
}
