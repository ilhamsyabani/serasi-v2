<?php

namespace App\Console\Commands;

use App\Models\StatusLog;
use App\Services\SlaCalculator;
use Illuminate\Console\Command;

class CloseStaleStatusLogs extends Command
{
    protected $signature = 'permohonan:close-stale-logs';

    protected $description = 'Tutup semua status_log yang masih aktif (waktu_selesai=null) berdasarkan log status berikutnya.';

    public function handle(SlaCalculator $sla): int
    {
        $stale = StatusLog::whereNull('waktu_selesai')->with('permohonan')->get();

        if ($stale->isEmpty()) {
            $this->info('Tidak ada status_log yang perlu ditutup.');
            return Command::SUCCESS;
        }

        $this->info("Menemukan {$stale->count()} status_log aktif.");

        $closed = 0;
        $errors = 0;

        foreach ($stale as $log) {
            // Cari log BERIKUTNYA berdasarkan permohonan yang sama, urut waktu_mulai
            $next = StatusLog::where('permohonan_id', $log->permohonan_id)
                ->where('waktu_mulai', '>', $log->waktu_mulai)
                ->orderBy('waktu_mulai')
                ->first();

            $waktuSelesai = $next ? $next->waktu_mulai : $log->permohonan->created_at;

            $durasiHk = $sla->hitungHariKerja($log->waktu_mulai, $waktuSelesai);

            $log->update([
                'waktu_selesai' => $waktuSelesai,
                'durasi_hari_kerja' => $durasiHk,
            ]);

            $this->line("  [{$log->id}] {$log->status} -> {$log->permohonan->no_registrasi}: selesai={$waktuSelesai}, hk={$durasiHk}");
            $closed++;
        }

        $this->info("Selesai. {$closed} log ditutup.");

        return Command::SUCCESS;
    }
}
