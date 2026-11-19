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
 * Pengingat WhatsApp "SLA hampir habis" ke staff penanggung jawab.
 * Cron: setiap jam pada jam kerja (diatur di routes/console.php).
 *
 * - Status SLA dihitung SlaCalculator (sla_config + hari_libur) — sumber yang sama
 *   dengan badge SLA di dashboard. Tahap revisi (clock-off) tidak diingatkan.
 * - Sekalian menyegarkan kolom `sla_deadline_current` & `is_overdue` semua
 *   permohonan aktif.
 * - Dikirim hanya untuk tahap tanggung jawab staff, saat sisa hari kerja termasuk
 *   config services.sla_reminder_hari (default H-2 & H-1).
 * - Maks. 1 pengingat per permohonan per hari. Setelah lewat batas, giliran
 *   notifikasi:sla-terlambat.
 */
class KirimSlaReminder extends Command
{
    public const TEMPLATE = 'SLA_MENDEKATI';

    /** Tahap yang menjadi tanggung jawab staff. */
    private const TAHAP_STAFF = [
        Permohonan::STATUS_PROSES_EVALUASI,
        Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
    ];

    protected $signature = 'notifikasi:sla-reminder {--dry-run : Jangan kirim, hanya tampilkan yang akan dikirim}';

    protected $description = 'Kirim WhatsApp pengingat ke staff untuk permohonan yang mendekati batas SLA (H-2 & H-1)';

    public function handle(NotifikasiService $notif, SlaCalculator $sla): int
    {
        $this->info('Mulai cek SLA reminder...');

        $hariPengingat = config('services.sla_reminder_hari', [2, 1]);
        $sekarang = now();

        $aktif = Permohonan::query()
            ->whereNotIn('status_saat_ini', [
                Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN,
                Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG,
                ...Permohonan::STATUS_REVISI,
            ])
            ->with(['statusLog', 'distribusiAktif.staff'])
            ->get();

        // Evaluasi SLA tahap berjalan; simpan untuk dipakai saat menyusun pesan.
        $hasilSla = [];
        $permohonans = $aktif->filter(function (Permohonan $p) use ($sla, $hariPengingat, &$hasilSla) {
            $hasil = $sla->isiDeadline($p);

            if (! $this->option('dry-run') && $p->isDirty(['sla_deadline_current', 'is_overdue'])) {
                // Kolom turunan saja — jangan geser updated_at / memicu event model.
                $p->timestamps = false;
                $p->saveQuietly();
                $p->timestamps = true;
            }

            $kirim = $hasil !== null
                && in_array($p->status_saat_ini, self::TAHAP_STAFF, true)
                && in_array($hasil['state'], [SlaCalculator::STATE_ON_TIME, SlaCalculator::STATE_AT_RISK], true)
                && in_array($hasil['sisa'], $hariPengingat, true);

            if ($kirim) {
                $hasilSla[$p->id] = $hasil;
            }

            return $kirim;
        });

        if ($permohonans->isEmpty()) {
            $this->info('Tidak ada permohonan yang mendekati batas SLA.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$permohonans->count()} permohonan yang mendekati deadline.");

        $templateAktif = TemplateNotifikasi::where('kode_event', self::TEMPLATE)
            ->where('channel', Notifikasi::CHANNEL_WHATSAPP)
            ->where('is_active', true)
            ->exists();

        if (! $templateAktif) {
            $this->warn('Template WhatsApp ' . self::TEMPLATE . ' tidak ada / nonaktif. Tidak ada yang dikirim.');
            return self::SUCCESS;
        }

        $namaTahap = $sla->konfigurasi()->map->nama_tahap;
        $terkirim = 0;

        foreach ($permohonans as $p) {
            $staff = $p->distribusiAktif?->staff;
            if (! $staff) {
                continue;
            }

            $hasil = $hasilSla[$p->id];

            $sudahKirim = Notifikasi::query()
                ->where('permohonan_id', $p->id)
                ->where('tujuan_tipe', Notifikasi::TUJUAN_STAFF)
                ->where('tujuan_id', $staff->id)
                ->where('template_kode', self::TEMPLATE)
                ->whereDate('created_at', $sekarang->toDateString())
                ->exists();

            if ($sudahKirim) {
                $this->line("  [SKIP] {$p->no_registrasi} — sudah dikirim hari ini");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [DRY] {$p->no_registrasi} → {$staff->nama} (sisa {$hasil['sisa']} HK)");
                $terkirim++;
                continue;
            }

            $result = $notif->kirimNotifikasiStaff($staff, $p, self::TEMPLATE, [
                '{{nama_staff}}' => $staff->nama,
                '{{tahap}}' => $namaTahap->get($p->status_saat_ini, $p->status_saat_ini),
                '{{deadline}}' => $hasil['deadline']?->translatedFormat('d M Y') ?? '-',
                '{{sisa}}' => (string) $hasil['sisa'],
            ]);

            if ($result?->status_kirim === Notifikasi::STATUS_TERKIRIM) {
                $terkirim++;
                Log::info('KirimSlaReminder: Terkirim', [
                    'permohonan_id' => $p->id,
                    'staff_id' => $staff->id,
                    'notifikasi_id' => $result->id,
                ]);
                $this->info("  [OK] {$p->no_registrasi} → {$staff->nama} (sisa {$hasil['sisa']} HK)");
            } else {
                $this->warn("  [GAGAL] {$p->no_registrasi} → {$staff->nama}");
            }
        }

        $this->info("Selesai. {$terkirim} reminder terkirim.");
        return self::SUCCESS;
    }
}
