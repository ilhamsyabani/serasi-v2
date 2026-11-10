<?php

namespace App\Console\Commands;

use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\TemplateNotifikasi;
use App\Services\NotifikasiService;
use App\Services\SlaCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Kirim WhatsApp ke staff penanggung jawab untuk permohonan yang SLA tahapnya
 * sudah terlampaui. Status terlambat dihitung SlaCalculator (sla_config + hari_libur);
 * tahap revisi clock-off otomatis tidak dianggap terlambat.
 */
class KirimNotifikasiSlaTerlambat extends Command
{
    public const TEMPLATE = 'SLA_TERLAMBAT';

    /** Tahap yang menjadi tanggung jawab staff. */
    private const TAHAP_STAFF = [
        Permohonan::STATUS_PROSES_EVALUASI,
        Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
    ];

    protected $signature = 'notifikasi:sla-terlambat {--dry-run : Hanya tampilkan, tidak mengirim}';

    protected $description = 'Kirim WhatsApp ke staff untuk permohonan yang melewati batas waktu SLA';

    public function handle(NotifikasiService $notif, SlaCalculator $sla): int
    {
        $templateAktif = TemplateNotifikasi::where('kode_event', self::TEMPLATE)
            ->where('channel', Notifikasi::CHANNEL_WHATSAPP)
            ->where('is_active', true)
            ->exists();

        if (! $templateAktif) {
            $this->warn('Template WhatsApp ' . self::TEMPLATE . ' tidak ada / nonaktif. Tidak ada yang dikirim.');
            return self::SUCCESS;
        }

        $permohonans = Permohonan::query()
            ->whereIn('status_saat_ini', self::TAHAP_STAFF)
            ->whereHas('distribusiAktif')
            ->with(['statusLog', 'distribusiAktif.staff'])
            ->get();

        $namaTahap = $sla->konfigurasi()->map->nama_tahap;
        $terkirim = 0;

        foreach ($permohonans as $p) {
            $hasil = $sla->evaluasiPermohonan($p);
            $staff = $p->distribusiAktif->staff;

            if (($hasil['state'] ?? null) !== SlaCalculator::STATE_LATE || ! $staff) {
                continue;
            }

            if ($this->sudahTerkirimHariIni($p, $staff->id)) {
                $this->line("  [SKIP] {$p->no_registrasi} — sudah dikirim hari ini");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [DRY] {$p->no_registrasi} → {$staff->nama} ({$hasil['label']})");
                $terkirim++;
                continue;
            }

            $result = $notif->kirimNotifikasiStaff($staff, $p, self::TEMPLATE, [
                '{{nama_staff}}' => $staff->nama,
                '{{tahap}}' => $namaTahap->get($p->status_saat_ini, $p->status_saat_ini),
                '{{deadline}}' => $hasil['deadline']?->translatedFormat('d M Y') ?? '-',
                '{{keterlambatan}}' => (string) abs($hasil['sisa']),
            ]);

            if ($result?->status_kirim === Notifikasi::STATUS_TERKIRIM) {
                $terkirim++;
                $this->info("  [OK] {$p->no_registrasi} → {$staff->nama}");
            } else {
                Log::warning('KirimNotifikasiSlaTerlambat: gagal kirim', [
                    'permohonan_id' => $p->id,
                    'staff_id' => $staff->id,
                    'error' => $result?->error_message ?? 'no_whatsapp staff kosong',
                ]);
                $this->warn("  [GAGAL] {$p->no_registrasi} → {$staff->nama}");
            }
        }

        $this->info("Selesai. {$terkirim} notifikasi SLA terlambat terkirim.");
        return self::SUCCESS;
    }

    private function sudahTerkirimHariIni(Permohonan $p, int $staffId): bool
    {
        return Notifikasi::query()
            ->where('permohonan_id', $p->id)
            ->where('tujuan_tipe', Notifikasi::TUJUAN_STAFF)
            ->where('tujuan_id', $staffId)
            ->where('template_kode', self::TEMPLATE)
            ->where('status_kirim', Notifikasi::STATUS_TERKIRIM)
            ->whereDate('created_at', today())
            ->exists();
    }
}
