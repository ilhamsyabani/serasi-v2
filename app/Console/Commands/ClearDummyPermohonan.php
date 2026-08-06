<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\DokumenPermohonan;
use App\Models\Evaluasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\ReassignmentLog;
use App\Models\Revisi;
use App\Models\StatusLog;
use App\Models\SuratPengesahan;
use Illuminate\Console\Command;

class ClearDummyPermohonan extends Command
{
    protected $signature = 'permohonan:clear-dummy';

    protected $description = 'Clear all dummy permohonan data (seeder output)';

    public function handle(): int
    {
        $count = Permohonan::count();

        if ($count === 0) {
            $this->info('No permohonan data to clear.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} permohonan records. Deleting...");

        $permohonanIds = Permohonan::pluck('id');

        Notifikasi::whereIn('permohonan_id', $permohonanIds)->delete();
        ReassignmentLog::whereIn('permohonan_id', $permohonanIds)->delete();
        SuratPengesahan::whereIn('permohonan_id', $permohonanIds)->delete();
        Revisi::whereIn('permohonan_id', $permohonanIds)->delete();
        Evaluasi::whereIn('permohonan_id', $permohonanIds)->delete();
        Distribusi::whereIn('permohonan_id', $permohonanIds)->delete();
        Disposisi::whereIn('permohonan_id', $permohonanIds)->delete();
        DokumenPermohonan::whereIn('permohonan_id', $permohonanIds)->delete();
        StatusLog::whereIn('permohonan_id', $permohonanIds)->delete();
        AuditTrail::whereIn('permohonan_id', $permohonanIds)->delete();

        Permohonan::whereIn('id', $permohonanIds)->delete();

        $this->info('Done. All permohonan data cleared.');

        return self::SUCCESS;
    }
}
