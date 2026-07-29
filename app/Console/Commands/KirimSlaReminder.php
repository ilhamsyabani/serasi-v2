<?php

namespace App\Console\Commands;

use App\Models\Distribusi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled command untuk mengirim SLA reminder ke staff.
 * Cron: setiap jam pada jam kerja (diatur di routes/console.php).
 *
 * Logic:
 * - Cek permohonan aktif (bukan final, bukan clock-off/revisi) yang deadline <= 24 jam
 * - Hanya kirim jika belum pernah dikirim hari ini (cegah spam)
 * - Target: staff penanggung jawab aktif
 */
class KirimSlaReminder extends Command
{
    protected $signature = 'notifikasi:sla-reminder {--dry-run : Jangan kirim, hanya tampilkan yang akan dikirim}';

    protected $description = 'Kirim WhatsApp reminder ke staff untuk permohonan yang mendekati batas SLA';

    public function handle(NotifikasiService $notif): int
    {
        $this->info('Mulai cek SLA reminder...');

        $batasJam = (int) config('services.sla_reminder_jam', 9);
        $tenggatJam = (int) config('services.sla_reminder_tenggat', 24);
        $sekarang = now();
        $deadline = $sekarang->copy()->addHours($tenggatJam)->endOfDay();

        $permohonans = Permohonan::query()
            ->whereNotIn('status_saat_ini', [
                Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN,
                Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG,
            ])
            ->whereNotIn('status_saat_ini', [
                Permohonan::STATUS_REVISI_1,
                Permohonan::STATUS_REVISI_2,
                Permohonan::STATUS_REVISI_3,
            ])
            ->where('is_overdue', false)
            ->whereNotNull('sla_deadline_current')
            ->where('sla_deadline_current', '<=', $deadline)
            ->where('sla_deadline_current', '>=', $sekarang->toDateString())
            ->with(['distribusiAktif.staff'])
            ->get();

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

            $result = $notif->kirim(
                $p,
                Notifikasi::TUJUAN_STAFF,
                $staff->id,
                Notifikasi::CHANNEL_WHATSAPP,
                'REMINDER'
            );

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
