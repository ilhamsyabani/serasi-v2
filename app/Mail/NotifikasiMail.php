<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifikasiMail extends Mailable
{
    use Queueable, SerializesModels;

    public $isi;
    public $templateKode;

    public function __construct(string $isi, ?string $templateKode = null)
    {
        $this->isi = $isi;
        $this->templateKode = $templateKode;
    }

    public function build()
    {
        return $this->subject('Notifikasi — Aplikasi Pengesahan Denah PBF')
                    ->view('emails.notifikasi');
    }
}
