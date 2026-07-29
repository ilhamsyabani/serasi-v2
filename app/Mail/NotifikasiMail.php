<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifikasiMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $isi;
    public ?string $templateKode;
    public string $subjek;

    public function __construct(string $isi, ?string $templateKode = null, ?string $subjek = null)
    {
        $this->isi = $isi;
        $this->templateKode = $templateKode;
        $this->subjek = $subjek ?? 'Notifikasi — Aplikasi Pengesahan Denah PBF';
    }

    public function build(): self
    {
        return $this->subject($this->subjek)
                    ->view('emails.notifikasi');
    }
}
