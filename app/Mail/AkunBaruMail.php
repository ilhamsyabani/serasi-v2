<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AkunBaruMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $username;
    public string $password;
    public string $namaPbf;

    public function __construct(string $username, string $password, string $namaPbf)
    {
        $this->username = $username;
        $this->password = $password;
        $this->namaPbf = $namaPbf;
    }

    public function build()
    {
        return $this->subject('Akun Portal Pelaku Usaha — Aplikasi Pengesahan Denah PBF')
                    ->view('emails.akun_baru');
    }
}
