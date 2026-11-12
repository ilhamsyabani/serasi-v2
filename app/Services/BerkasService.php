<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Penyimpanan berkas permohonan (dokumen persyaratan, berkas revisi, surat pengesahan).
 *
 * Berkas disimpan di disk PRIVAT (`storage/app/private`), yang tidak terjangkau lewat
 * URL — satu-satunya jalan membukanya adalah DownloadController, yang memeriksa login
 * dan hak akses. Jangan pernah menyimpan berkas permohonan di disk `public`: apa pun
 * di sana bisa dibuka siapa saja lewat `/storage/...` tanpa login.
 *
 * Berkas lama yang masih di disk `public` tetap bisa dibaca (fallback) sampai
 * dipindahkan dengan `php artisan berkas:amankan`.
 */
class BerkasService
{
    public const DISK = 'local';
    public const DISK_LAMA = 'public';

    /** Folder berkas permohonan — dipakai juga oleh command pemindahan. */
    public const FOLDER = ['dokumen_permohonan', 'dokumen_revisi', 'surat_pengesahan'];

    /** Simpan berkas unggahan ke disk privat; mengembalikan path relatif untuk kolom `path_file`. */
    public function simpan(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, self::DISK);
    }

    /** Hapus berkas di mana pun ia berada (privat maupun sisa di public). */
    public function hapus(string|array|null $paths): void
    {
        $paths = array_filter((array) $paths);
        if ($paths === []) {
            return;
        }

        Storage::disk(self::DISK)->delete($paths);
        Storage::disk(self::DISK_LAMA)->delete($paths);
    }

    /** Path fisik berkas untuk dikirim ke browser, atau null bila berkas tidak ada. */
    public function pathFisik(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        foreach ([self::DISK, self::DISK_LAMA] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->path($path);
            }
        }

        return null;
    }
}
