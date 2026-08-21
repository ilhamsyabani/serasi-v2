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
    public string $noRegistrasi;
    public string $nib;
    public string $alamat;

    public function __construct(
        string $username,
        string $password,
        string $namaPbf,
        string $noRegistrasi,
        string $nib,
        string $alamat,
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->namaPbf = $namaPbf;
        $this->noRegistrasi = $noRegistrasi;
        $this->nib = $nib;
        $this->alamat = $alamat;
    }

    public function build()
    {
        return $this->subject('Akun Portal Pelaku Usaha — Aplikasi Pengesahan Denah PBF')
                    ->to($this->username)
                    ->view('emails.akun_baru');
    }
}
