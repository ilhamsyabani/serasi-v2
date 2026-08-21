<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\TemplateNotifikasi;
use App\Mail\NotifikasiMail;
use App\Mail\AkunBaruMail;
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
                } elseif (!$this->whatsappSender()->sendWithRetry($noWa, $isi)) {
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
     * Kirim kredensial akun baru (AKUN_BARU) — template placeholder.
     * Dipanggil dari Kabalai\PermohonanController::store().
     * Mengirim ke WA dan email sekaligus.
     */
    public function kirimAkunBaru(Permohonan $permohonan, string $username, string $password): array
    {
        $results = [];

        // WhatsApp via template
        $results[] = $this->kirim(
            $permohonan,
            Notifikasi::TUJUAN_PEMOHON,
            $permohonan->pbf_id,
            Notifikasi::CHANNEL_WHATSAPP,
            'AKUN_BARU',
            null,
            [
                '{{username}}'  => $username,
                '{{password}}'  => $password,
                '{{app_url}}'  => config('app.url'),
                '{{nama_pbf}}' => $permohonan->nama_pbf_snapshot,
                '{{no_registrasi}}' => $permohonan->no_registrasi,
                '{{nib}}'      => $permohonan->nib_snapshot,
                '{{alamat}}'   => $permohonan->pbf->alamat ?? '-',
            ]
        );

        // Email via AkunBaruMail (styled, sync — langsung terkirim)
        $results[] = $this->kirimAkunBaruEmail($permohonan, $username, $password);

        return $results;
    }

    /**
     * Kirim email akun baru via queue menggunakan AkunBaruMail (styled template).
     */
    private function kirimAkunBaruEmail(Permohonan $permohonan, string $username, string $password): Notifikasi
    {
        $email = $permohonan->pbf->email;
        $status = Notifikasi::STATUS_TERKIRIM;

        try {
            Mail::to($email)->send(new AkunBaruMail(
                $username,
                $password,
                $permohonan->nama_pbf_snapshot,
                $permohonan->no_registrasi,
                $permohonan->nib_snapshot,
                $permohonan->pbf->alamat ?? '-',
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NotifikasiService: Gagal queue email akun baru', [
                'permohonan_id' => $permohonan->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            $status = Notifikasi::STATUS_GAGAL;
        }

        return Notifikasi::create([
            'permohonan_id' => $permohonan->id,
            'tujuan_tipe'   => Notifikasi::TUJUAN_PEMOHON,
            'tujuan_id'     => $permohonan->pbf_id,
            'channel'       => Notifikasi::CHANNEL_EMAIL,
            'template_kode' => 'AKUN_BARU',
            'status_kirim'  => $status,
        ]);
    }

    private function resolveNomorWa(Permohonan $permohonan, string $tujuanTipe, int $tujuanId): ?string
    {
        if ($tujuanTipe === Notifikasi::TUJUAN_PEMOHON) {
            $noWa = $permohonan->pbf->no_whatsapp ?? null;
            if (!$noWa) {
                \Illuminate\Support\Facades\Log::warning('NotifikasiService: no_whatsapp kosong untuk pemohon', [
                    'permohonan_id' => $permohonan->id,
                    'pbf_id' => $permohonan->pbf_id,
                    'pbf_nama' => $permohonan->nama_pbf_snapshot,
                ]);
            }
            return $noWa;
        }
        $user = \App\Models\User::find($tujuanId);
        if (!$user) {
            \Illuminate\Support\Facades\Log::warning('NotifikasiService: User tidak ditemukan', [
                'tujuan_tipe' => $tujuanTipe,
                'tujuan_id' => $tujuanId,
            ]);
            return null;
        }
        if (empty($user->no_whatsapp)) {
            \Illuminate\Support\Facades\Log::warning('NotifikasiService: no_whatsapp kosong untuk user internal', [
                'user_id' => $tujuanId,
                'user_nama' => $user->nama,
                'user_role' => $user->role?->nama,
            ]);
        }
        return $user->no_whatsapp;
    }

    private function resolveEmail(Permohonan $permohonan, string $tujuanTipe, int $tujuanId): ?string
    {
        if ($tujuanTipe === Notifikasi::TUJUAN_PEMOHON) {
            return $permohonan->pbf->email;
        }
        return \App\Models\User::find($tujuanId)?->email;
    }

    /**
     * Kirim notifikasi WA ke staff/ketua_tim dengan retry. Jika no_whatsapp kosong,
     * lempar null agar caller aware — caller HARUS cek return value.
     * Caller: DistribusiController, DisposisiController, RevisiController, EvaluasiController.
     */
    public function kirimNotifikasiStaff(
        \App\Models\User $penerima,
        Permohonan $permohonan,
        string $templateKode,
    ): ?Notifikasi {
        if (empty($penerima->no_whatsapp)) {
            \Illuminate\Support\Facades\Log::warning('kirimNotifikasiStaff: no_whatsapp kosong', [
                'staff_id' => $penerima->id,
                'staff_nama' => $penerima->nama,
                'permohonan_id' => $permohonan->id,
                'template' => $templateKode,
            ]);
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
            \Illuminate\Support\Facades\Log::warning('kirimNotifikasiKetuaTim: no_whatsapp kosong', [
                'ketua_tim_id' => $penerima->id,
                'ketua_tim_nama' => $penerima->nama,
                'permohonan_id' => $permohonan->id,
                'template' => $templateKode,
            ]);
            return null;
        }
        return $this->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $penerima->id, Notifikasi::CHANNEL_WHATSAPP, $templateKode);
    }

    private function whatsappSender(): WhatsappSender
    {
        return app(WhatsappSender::class);
    }
}
