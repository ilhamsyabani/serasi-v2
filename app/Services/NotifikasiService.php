<?php

namespace App\Services;

use App\Models\AuditTrail;
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

        $status = 'terkirim';
        try {
            if ($channel === 'email') {
                $tujuanEmail = $tujuanTipe === 'pemohon' ? $permohonan->pbf->email : \App\Models\User::find($tujuanId)?->email;
                if ($tujuanEmail) {
                    Mail::to($tujuanEmail)->send(new NotifikasiMail($isi, $templateKode));
                }
            }
        } catch (\Throwable $e) {
            $status = 'gagal';
        }

        return Notifikasi::create([
            'permohonan_id' => $permohonan->id,
            'tujuan_tipe' => $tujuanTipe,
            'tujuan_id' => $tujuanId,
            'channel' => $channel,
            'template_kode' => $templateKode,
            'status_kirim' => $status,
        ]);
    }
}
