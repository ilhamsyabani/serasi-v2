<?php

namespace App\Console\Commands;

use App\Services\BerkasService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Memindahkan berkas permohonan lama dari disk `public` (terbuka lewat URL
 * `/storage/...` tanpa login) ke disk privat. Path relatif tidak berubah, jadi
 * kolom `path_file` di database tidak perlu disentuh.
 *
 * Aman dijalankan berulang. Jalankan sekali di server setelah deploy.
 */
class AmankanBerkas extends Command
{
    protected $signature = 'berkas:amankan {--dry-run : Hanya tampilkan berkas yang akan dipindahkan}';

    protected $description = 'Pindahkan berkas permohonan dari disk public ke disk privat';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $publik = Storage::disk(BerkasService::DISK_LAMA);
        $privat = Storage::disk(BerkasService::DISK);

        if ($dryRun) {
            $this->warn('Mode dry-run: tidak ada berkas yang dipindahkan.');
        }

        $dipindah = 0;
        $duplikat = 0;
        $gagal = 0;

        foreach (BerkasService::FOLDER as $folder) {
            foreach ($publik->allFiles($folder) as $path) {
                // Sudah ada di privat (mis. command sempat terputus): cukup buang salinan publiknya.
                if ($privat->exists($path)) {
                    $duplikat++;
                    if (! $dryRun) {
                        $publik->delete($path);
                    }
                    continue;
                }

                if ($dryRun) {
                    $dipindah++;
                    continue;
                }

                $tujuan = $privat->path($path);
                File::ensureDirectoryExists(dirname($tujuan));

                // Salin dulu, verifikasi ukuran, baru hapus sumber — jangan sampai berkas hilang.
                if (File::copy($publik->path($path), $tujuan) && File::size($tujuan) === $publik->size($path)) {
                    $publik->delete($path);
                    $dipindah++;
                } else {
                    File::delete($tujuan);
                    $gagal++;
                    $this->error("  Gagal memindahkan: {$path}");
                }
            }
        }

        if (! $dryRun) {
            $this->tulisPenjaga($privat->path(''));
        }

        $this->info(($dryRun ? 'Akan dipindahkan' : 'Dipindahkan') . ": {$dipindah} berkas. Salinan ganda dibersihkan: {$duplikat}. Gagal: {$gagal}.");

        $sisa = collect(BerkasService::FOLDER)->sum(fn ($f) => count($publik->allFiles($f)));
        if (! $dryRun && $sisa > 0) {
            $this->warn("Masih ada {$sisa} berkas di disk public — periksa izin folder lalu jalankan ulang.");
        }

        $this->laporBerkasHilang();

        return $gagal > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Pengaman lapis kedua untuk hosting yang document root-nya keliru menunjuk ke
     * folder proyek (bukan `public/`): tolak akses web langsung ke folder privat.
     */
    private function tulisPenjaga(string $root): void
    {
        File::ensureDirectoryExists($root);
        $htaccess = rtrim($root, '/') . '/.htaccess';

        if (! File::exists($htaccess)) {
            File::put($htaccess, "Require all denied\n");
        }
    }

    /** Baris database yang berkasnya tidak ditemukan di disk mana pun — informasi saja. */
    private function laporBerkasHilang(): void
    {
        $berkas = app(BerkasService::class);
        $hilang = 0;

        foreach (['dokumen_permohonan', 'dokumen_revisi', 'surat_pengesahan'] as $tabel) {
            foreach (\Illuminate\Support\Facades\DB::table($tabel)->pluck('path_file') as $path) {
                if ($berkas->pathFisik($path) === null) {
                    $hilang++;
                }
            }
        }

        if ($hilang > 0) {
            $this->warn("Catatan: {$hilang} baris di database merujuk berkas yang tidak ada di server ini.");
        }
    }
}
