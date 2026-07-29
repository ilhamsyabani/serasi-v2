<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\TemplateNotifikasi;
use App\Mail\NotifikasiMail;
use Illuminate\Support\Facades\Mail;

class NotifikasiService
{
    public function kirim(Permohonan $permohonan, string $tujuanTipe, int $tujuanId, string $channel, ?string $templateKode = null, ?string $pesanManual = null): Notifikasi
    {
        $isi = $pesanManual;
        if (!$isi && $templateKode) {
            $template = TemplateNotifikasi::where('kode_event', $templateKode)->where('channel', $channel)->where('is_active', true)->first();
            $isi = $template?->isi_template ?? '';
            $isi = str_replace('{{no_registrasi}}', $permohonan->no_registrasi, $isi);
            $isi = str_replace('{{nama_pbf}}', $permohonan->nama_pbf_snapshot, $isi);
        }

        $status = Notifikasi::STATUS_TERKIRIM;
        try {
            if ($channel === Notifikasi::CHANNEL_EMAIL) {
                $tujuanEmail = $tujuanTipe === Notifikasi::TUJUAN_PEMOHON
                    ? $permohonan->pbf->email
                    : \App\Models\User::find($tujuanId)?->email;
                if ($tujuanEmail) {
                    Mail::to($tujuanEmail)->send(new NotifikasiMail($isi, $templateKode));
                }
            } elseif ($channel === Notifikasi::CHANNEL_WHATSAPP) {
                $noWa = $this->resolveNomorWa($permohonan, $tujuanTipe, $tujuanId);
                if ($noWa) {
                    $sent = app(WhatsappSender::class)->send($noWa, $isi);
                    $status = $sent ? Notifikasi::STATUS_TERKIRIM : Notifikasi::STATUS_GAGAL;
                } else {
                    $status = Notifikasi::STATUS_GAGAL;
                }
            }
        } catch (\Throwable $e) {
            $status = Notifikasi::STATUS_GAGAL;
        }

        return Notifikasi::create([
            'permohonan_id' => $permohonan->id,
            'tujuan_tipe'   => $tujuanTipe,
            'tujuan_id'     => $tujuanId,
            'channel'       => $channel,
            'template_kode' => $templateKode,
            'status_kirim'  => $status,
        ]);
    }

    private function resolveNomorWa(Permohonan $permohonan, string $tujuanTipe, int $tujuanId): ?string
    {
        if ($tujuanTipe === Notifikasi::TUJUAN_PEMOHON) {
            return $permohonan->pbf->no_whatsapp;
        }
        $user = \App\Models\User::find($tujuanId);
        return $user?->no_whatsapp;
    }
}
