<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Services\SlaCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menyeragamkan data SLA dengan aturan hitung yang berlaku di SlaCalculator
 * (hari kerja per tanggal 00:00–23:59, hitungan mulai H+1):
 *
 * 1. `status_log.durasi_hari_kerja` untuk tahap yang sudah selesai dihitung ulang
 *    dari `waktu_mulai`/`waktu_selesai` — kedua waktu itu TIDAK diubah, hanya
 *    angka turunannya.
 * 2. `permohonan.sla_deadline_current` & `is_overdue` diisi untuk tahap berjalan.
 *
 * Aman dijalankan berulang (idempoten). Jalankan juga setelah Admin IT mengubah
 * `hari_libur` bila durasi tahap lama perlu mengikuti kalender terbaru.
 */
class HitungUlangSla extends Command
{
    protected $signature = 'sla:hitung-ulang {--dry-run : Hanya tampilkan perubahan, tidak menyimpan}';

    protected $description = 'Hitung ulang durasi hari kerja status_log & deadline SLA permohonan sesuai aturan terbaru';

    public function handle(SlaCalculator $sla): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Mode dry-run: tidak ada data yang disimpan.');
        }

        [$logDiperiksa, $logBerubah] = $this->hitungUlangDurasi($sla, $dryRun);
        $deadlineBerubah = $this->sinkronkanDeadline($sla, $dryRun);

        if (! $dryRun && ($logBerubah > 0 || $deadlineBerubah > 0)) {
            AuditTrail::create([
                'user_id' => 0,
                'user_type' => 'system',
                'aksi' => 'sla_hitung_ulang',
                'modul' => 'M-16',
                'permohonan_id' => null,
                'detail' => [
                    'log_diperiksa' => $logDiperiksa,
                    'log_diubah' => $logBerubah,
                    'deadline_diubah' => $deadlineBerubah,
                ],
            ]);
        }

        $this->newLine();
        $this->info("Status log selesai diperiksa: {$logDiperiksa}, durasi " . ($dryRun ? 'akan diubah' : 'diubah') . ": {$logBerubah}.");
        $this->info('Permohonan dengan deadline/flag telat ' . ($dryRun ? 'akan diubah' : 'diubah') . ": {$deadlineBerubah}.");

        return self::SUCCESS;
    }

    /** @return array{0:int,1:int} [jumlah diperiksa, jumlah berubah] */
    private function hitungUlangDurasi(SlaCalculator $sla, bool $dryRun): array
    {
        $diperiksa = 0;
        $berubah = 0;

        StatusLog::query()
            ->whereNotNull('waktu_selesai')
            ->whereHas('permohonan')
            ->with('permohonan:id,no_registrasi')
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($sla, $dryRun, &$diperiksa, &$berubah) {
                foreach ($logs as $log) {
                    $diperiksa++;
                    $baru = $sla->hitungHariKerja($this->awalHitung($log), $log->waktu_selesai);

                    if ($log->durasi_hari_kerja === $baru) {
                        continue;
                    }

                    $berubah++;
                    $lama = $log->durasi_hari_kerja ?? '-';
                    $this->line("  [{$log->id}] {$log->permohonan->no_registrasi} {$log->status}: {$lama} -> {$baru} hk");

                    if (! $dryRun) {
                        // Query langsung: hanya kolom turunan, tanpa menyentuh waktu_mulai/selesai.
                        DB::table('status_log')->where('id', $log->id)->update(['durasi_hari_kerja' => $baru]);
                    }
                }
            });

        return [$diperiksa, $berubah];
    }

    /**
     * Titik awal hitung durasi sebuah log. Tahap `terbit_surat_pengesahan` langsung
     * ditutup saat dibuat, sehingga durasinya dihitung sejak tahap
     * `menunggu_surat_pengesahan` dimulai — sama seperti StatusTransitionService.
     */
    private function awalHitung(StatusLog $log)
    {
        if ($log->status !== Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN) {
            return $log->waktu_mulai;
        }

        return StatusLog::where('permohonan_id', $log->permohonan_id)
            ->where('status', Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN)
            ->where('waktu_mulai', '<=', $log->waktu_mulai)
            ->orderByDesc('waktu_mulai')
            ->value('waktu_mulai') ?? $log->waktu_mulai;
    }

    private function sinkronkanDeadline(SlaCalculator $sla, bool $dryRun): int
    {
        $berubah = 0;

        Permohonan::with('statusLog')->orderBy('id')->chunkById(200, function ($permohonans) use ($sla, $dryRun, &$berubah) {
            foreach ($permohonans as $p) {
                $sla->isiDeadline($p);

                if (! $p->isDirty(['sla_deadline_current', 'is_overdue'])) {
                    continue;
                }

                $berubah++;
                $deadline = $p->sla_deadline_current?->toDateString() ?? '-';
                $telat = $p->is_overdue ? 'ya' : 'tidak';
                $this->line("  {$p->no_registrasi} ({$p->status_saat_ini}): deadline {$deadline}, telat {$telat}");

                if (! $dryRun) {
                    // Kolom turunan saja — jangan geser updated_at / memicu event model.
                    $p->timestamps = false;
                    $p->saveQuietly();
                }
            }
        });

        return $berubah;
    }
}
