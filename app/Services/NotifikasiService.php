<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\TemplateNotifikasi;
use App\Mail\NotifikasiMail;
use Illuminate\Support\Facades\Mail;

class NotifikasiService
{
    /**
     * Kirim satu notifikasi. Untuk penggunaan langsung dari controller/event.
     *
     * @param Permohonan $permohonan
     * @param string     $tujuanTipe  'pemohon'|'staff'|'ketua_tim'|'kepala_balai'
     * @param int        $tujuanId    pbf.id atau users.id
     * @param string     $channel     'email'|'whatsapp'
     * @param string|null $templateKode
     * @param string|null $pesanManual Isi pesan override (mengabaikan template)
     * @param array      $placeholders Placeholder tambahan, mis. ['{{username}}' => 'foo']
     */
    public function kirim(
        Permohonan $permohonan,
        string $tujuanTipe,
        int $tujuanId,
        string $channel,
        ?string $templateKode = null,
        ?string $pesanManual = null,
        array $placeholders = [],
    ): Notifikasi {
        $isi = $pesanManual;
        $subjek = null;

        if (!$isi && $templateKode) {
            $template = TemplateNotifikasi::where('kode_event', $templateKode)
                ->where('channel', $channel)
                ->where('is_active', true)
                ->first();
            $isi = $template?->isi_template ?? '';
            $subjek = $template?->subjek;

            // Placeholder standar
            $isi = str_replace('{{no_registrasi}}', $permohonan->no_registrasi, $isi);
            $isi = str_replace('{{nama_pbf}}', $permohonan->nama_pbf_snapshot, $isi);

            // Placeholder tambahan (mis. username, password, app_url)
            foreach ($placeholders as $key => $val) {
                $isi = str_replace($key, $val, $isi);
            }
        }

        $status = Notifikasi::STATUS_TERKIRIM;

        try {
            if ($channel === Notifikasi::CHANNEL_EMAIL) {
                $tujuanEmail = $this->resolveEmail($permohonan, $tujuanTipe, $tujuanId);
                if ($tujuanEmail) {
                    Mail::to($tujuanEmail)->send(
                        new NotifikasiMail($isi, $templateKode, $subjek)
                    );
                }
            } elseif ($channel === Notifikasi::CHANNEL_WHATSAPP) {
                $noWa = $this->resolveNomorWa($permohonan, $tujuanTipe, $tujuanId);
                if (!$noWa) {
                    $nama = $tujuanTipe === Notifikasi::TUJUAN_PEMOHON
                        ? $permohonan->pbf->nama
                        : \App\Models\User::find($tujuanId)?->nama;
                    \Illuminate\Support\Facades\Log::warning('NotifikasiService: Nomor WA kosong', [
                        'permohonan_id' => $permohonan->id,
                        'tujuan_tipe'  => $tujuanTipe,
                        'tujuan_id'    => $tujuanId,
                        'nama'         => $nama,
                    ]);
                    $status = Notifikasi::STATUS_GAGAL;
                } elseif (!$this->whatsappSender()->send($noWa, $isi)) {
                    $status = Notifikasi::STATUS_GAGAL;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NotifikasiService: Gagal kirim', [
                'permohonan_id' => $permohonan->id,
                'tujuan_tipe'   => $tujuanTipe,
                'channel'      => $channel,
                'error'        => $e->getMessage(),
            ]);
            $status = Notifikasi::STATUS_GAGAL;
        }

        return Notifikasi::create([
            'permohonan_id' => $permohonan->id,
            'tujuan_tipe'   => $tujuanTipe,
            'tujuan_id'     => $tujuanId,
            'channel'       => $channel,
            'template_kode'  => $templateKode,
            'status_kirim'  => $status,
        ]);
    }

    /**
     * Kirim ke beberapa penerima sekaligus.
     *
     * @param Permohonan $permohonan
     * @param array     $recipients  [[tujuanTipe, tujuanId, channel], ...]
     * @param string|null $templateKode
     * @param array      $placeholders
     * @return array  Notifikasi[]  hasil log per penerima
     */
    public function kirimBatch(
        Permohonan $permohonan,
        array $recipients,
        ?string $templateKode = null,
        array $placeholders = [],
    ): array {
        $results = [];
        foreach ($recipients as $r) {
            [$tujuanTipe, $tujuanId, $channel] = $r;
            $results[] = $this->kirim(
                $permohonan, $tujuanTipe, $tujuanId, $channel,
                $templateKode, null, $placeholders
            );
        }
        return $results;
    }

    /**
     * Kirim notifikasi yang TIDAK terkait permohonan spesifik (mis. REMINDER
     * dari scheduled job atau notifikasi sistem umum).
     *
     * @param string     $tujuanTipe
     * @param int        $tujuanId
     * @param string     $channel
     * @param string     $templateKode
     * @param array      $placeholders
     * @param string|null $catatan       Isi pesan (bukan template lookup)
     * @param string|null $subjek         Untuk email
     */
    public function kirimTanpaPermohonan(
        string $tujuanTipe,
        int $tujuanId,
        string $channel,
        string $templateKode,
        array $placeholders = [],
        ?string $catatan = null,
        ?string $subjek = null,
    ): ?Notifikasi {
        $template = TemplateNotifikasi::where('kode_event', $templateKode)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null;
        }

        $isi = $template->isi_template;
        foreach ($placeholders as $key => $val) {
            $isi = str_replace($key, $val, $isi);
        }

        if ($subjek === null) {
            $subjek = $template->subjek;
        }

        $status = Notifikasi::STATUS_TERKIRIM;

        try {
            if ($channel === Notifikasi::CHANNEL_EMAIL) {
                $email = $tujuanTipe === Notifikasi::TUJUAN_PEMOHON
                    ? \App\Models\Pbf::find($tujuanId)?->email
                    : \App\Models\User::find($tujuanId)?->email;
                if ($email) {
                    Mail::to($email)->send(new NotifikasiMail($isi, $templateKode, $subjek));
                }
            } elseif ($channel === Notifikasi::CHANNEL_WHATSAPP) {
                $noWa = $tujuanTipe === Notifikasi::TUJUAN_PEMOHON
                    ? \App\Models\Pbf::find($tujuanId)?->no_whatsapp
                    : \App\Models\User::find($tujuanId)?->no_whatsapp;
                if (!$noWa) {
                    $nama = $tujuanTipe === Notifikasi::TUJUAN_PEMOHON
                        ? \App\Models\Pbf::find($tujuanId)?->nama
                        : \App\Models\User::find($tujuanId)?->nama;
                    \Illuminate\Support\Facades\Log::warning('NotifikasiService: Nomor WA kosong', [
                        'tujuan_tipe' => $tujuanTipe,
                        'tujuan_id'   => $tujuanId,
                        'nama'        => $nama,
                    ]);
                    $status = Notifikasi::STATUS_GAGAL;
                } else {
                    $sent = app(WhatsappSender::class)->send($noWa, $isi);
                    $status = $sent ? Notifikasi::STATUS_TERKIRIM : Notifikasi::STATUS_GAGAL;
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NotifikasiService: Gagal kirim tanpa permohonan', [
                'tujuan_tipe'   => $tujuanTipe,
                'tujuan_id'    => $tujuanId,
                'channel'      => $channel,
                'template_kode' => $templateKode,
                'error'        => $e->getMessage(),
            ]);
            $status = Notifikasi::STATUS_GAGAL;
        }

        return Notifikasi::create([
            'permohonan_id' => null,
            'tujuan_tipe'   => $tujuanTipe,
            'tujuan_id'     => $tujuanId,
            'channel'       => $channel,
            'template_kode' => $templateKode,
            'status_kirim'  => $status,
        ]);
    }

    /**
     * Kirim kredensial akun baru (AKUN_BARU) — sudah pakai template dengan placeholder.
     * Dipanggil dari Kabalai\PermohonanController::store().
     */
    public function kirimAkunBaru(Permohonan $permohonan, string $username, string $password): array
    {
        return $this->kirimBatch(
            $permohonan,
            [
                [Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_WHATSAPP],
            ],
            'AKUN_BARU',
            [
                '{{username}}' => $username,
                '{{password}}' => $password,
                '{{app_url}}'  => config('app.url'),
            ]
        );
    }

    private function resolveNomorWa(Permohonan $permohonan, string $tujuanTipe, int $tujuanId): ?string
    {
        if ($tujuanTipe === Notifikasi::TUJUAN_PEMOHON) {
            return $permohonan->pbf->no_whatsapp;
        }
        $user = \App\Models\User::find($tujuanId);
        return $user?->no_whatsapp;
    }

    private function resolveEmail(Permohonan $permohonan, string $tujuanTipe, int $tujuanId): ?string
    {
        if ($tujuanTipe === Notifikasi::TUJUAN_PEMOHON) {
            return $permohonan->pbf->email;
        }
        return \App\Models\User::find($tujuanId)?->email;
    }

    /**
     * Kirim notifikasi WA ke staff/ketua_tim. Jika no_whatsapp kosong, lempar
     * User object agar caller bisa decide — lempar null jika caller tidak mau.
     * Caller: DistribusiController, DisposisiController, ReassignmentLog, PermohonanNotifier.
     */
    public function kirimNotifikasiStaff(
        \App\Models\User $penerima,
        Permohonan $permohonan,
        string $templateKode,
    ): ?Notifikasi {
        if (empty($penerima->no_whatsapp)) {
            return null;
        }
        return $this->kirim($permohonan, Notifikasi::TUJUAN_STAFF, $penerima->id, Notifikasi::CHANNEL_WHATSAPP, $templateKode);
    }

    public function kirimNotifikasiKetuaTim(
        \App\Models\User $penerima,
        Permohonan $permohonan,
        string $templateKode,
    ): ?Notifikasi {
        if (empty($penerima->no_whatsapp)) {
            return null;
        }
        return $this->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $penerima->id, Notifikasi::CHANNEL_WHATSAPP, $templateKode);
    }

    private function whatsappSender(): WhatsappSender
    {
        return app(WhatsappSender::class);
    }
}
