<?php

namespace App\Console\Commands;

use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Services\NotifikasiService;
use App\Services\SlaCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled command untuk mengirim SLA reminder ke staff.
 * Cron: setiap jam pada jam kerja (diatur di routes/console.php).
 *
 * Logic:
 * - Evaluasi setiap permohonan aktif (bukan final, bukan clock-off/revisi) lewat
 *   SlaCalculator — sumber kebenaran yang sama dengan badge SLA di dashboard.
 * - Sekalian segarkan kolom `sla_deadline_current` & `is_overdue`.
 * - Kirim bila belum telat dan sisa hari kerja <= ambang (default 1 HK, dari
 *   config services.sla_reminder_tenggat dalam jam, dibulatkan ke hari kerja).
 * - Hanya kirim jika belum pernah dikirim hari ini (cegah spam)
 * - Target: staff penanggung jawab aktif
 */
class KirimSlaReminder extends Command
{
    protected $signature = 'notifikasi:sla-reminder {--dry-run : Jangan kirim, hanya tampilkan yang akan dikirim}';

    protected $description = 'Kirim WhatsApp reminder ke staff untuk permohonan yang mendekati batas SLA';

    public function handle(NotifikasiService $notif, SlaCalculator $sla): int
    {
        $this->info('Mulai cek SLA reminder...');

        $tenggatJam = (int) config('services.sla_reminder_tenggat', 24);
        $ambangHari = max(1, (int) ceil($tenggatJam / 24));
        $sekarang = now();

        $aktif = Permohonan::query()
            ->whereNotIn('status_saat_ini', [
                Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN,
                Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG,
                Permohonan::STATUS_REVISI_1,
                Permohonan::STATUS_REVISI_2,
                Permohonan::STATUS_REVISI_3,
                Permohonan::STATUS_REVISI_4,
            ])
            ->with(['statusLog', 'distribusiAktif.staff'])
            ->get();

        $permohonans = $aktif->filter(function (Permohonan $p) use ($sla, $ambangHari) {
            $hasil = $sla->isiDeadline($p);

            if (! $this->option('dry-run') && $p->isDirty(['sla_deadline_current', 'is_overdue'])) {
                // Kolom turunan saja — jangan geser updated_at / memicu event model.
                $p->timestamps = false;
                $p->saveQuietly();
                $p->timestamps = true;
            }

            return $hasil !== null
                && in_array($hasil['state'], [SlaCalculator::STATE_ON_TIME, SlaCalculator::STATE_AT_RISK], true)
                && $hasil['sisa'] <= $ambangHari;
        });

        if ($permohonans->isEmpty()) {
            $this->info('Tidak ada permohonan yang mendekati batas SLA.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$permohonans->count()} permohonan yang mendekati deadline.");

        $terkirim = 0;
        foreach ($permohonans as $p) {
            $staff = $p->distribusiAktif?->staff;
            if (!$staff) {
                continue;
            }

            // Cek apakah sudah dikirim hari ini (prevengo spam)
            $sudahKirim = Notifikasi::query()
                ->where('permohonan_id', $p->id)
                ->where('tujuan_tipe', Notifikasi::TUJUAN_STAFF)
                ->where('tujuan_id', $staff->id)
                ->where('template_kode', 'REMINDER')
                ->whereDate('created_at', $sekarang->toDateString())
                ->exists();

            if ($sudahKirim) {
                $this->line("  [SKIP] {$p->no_registrasi} — sudah dikirim hari ini");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [DRY] {$p->no_registrasi} → {$staff->nama}");
                $terkirim++;
                continue;
            }

            $result = $notif->kirimNotifikasiStaff($staff, $p, 'REMINDER');

            if ($result) {
                $terkirim++;
                Log::info('KirimSlaReminder: Terkirim', [
                    'permohonan_id' => $p->id,
                    'staff_id'     => $staff->id,
                    'notifikasi_id'=> $result->id,
                ]);
                $this->info("  [OK] {$p->no_registrasi} → {$staff->nama}");
            } else {
                $this->warn("  [GAGAL] {$p->no_registrasi} → {$staff->nama}");
            }
        }

        $this->info("Selesai. {$terkirim} reminder terkirim.");
        return self::SUCCESS;
    }
}
