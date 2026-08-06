<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $kode;
    public string $resetUrl;

    public function __construct(string $resetUrl)
    {
        $this->kode = '';
        $this->resetUrl = $resetUrl;
    }

    public function build()
    {
        return $this->subject('Reset Password — Portal Pemohon')
                    ->view('emails.password-reset');
    }
}
