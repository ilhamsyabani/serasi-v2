<?php

namespace App\Http\Controllers;

use App\Services\BerkasService;
use Illuminate\Support\Facades\Gate;

class DownloadController extends Controller
{
    public function dokumen(int $permohonanId, string $jenisDokumen)
    {
        $dokumen = \App\Models\DokumenPermohonan::where('permohonan_id', $permohonanId)
            ->where('jenis_dokumen', $jenisDokumen)
            ->latest('versi')
            ->firstOrFail();

        abort_unless(
            $this->canAccessPermohonan($dokumen->permohonan),
            403
        );

        return $this->previewFile($dokumen->path_file, $dokumen->nama_file_asli);
    }

    public function revisi(int $revisiId)
    {
        $dokumen = \App\Models\DokumenRevisi::findOrFail($revisiId);

        abort_unless(
            $this->canAccessPermohonan($dokumen->revisi->permohonan),
            403
        );

        return $this->previewFile($dokumen->path_file, $dokumen->nama_file_asli);
    }

    public function surat(int $permohonanId)
    {
        $surat = \App\Models\SuratPengesahan::where('permohonan_id', $permohonanId)
            ->latest('id')
            ->firstOrFail();

        abort_unless(
            $this->canAccessPermohonan($surat->permohonan),
            403
        );

        return $this->previewFile($surat->path_file, $surat->nomor_surat . '.pdf');
    }

    private function previewFile(string $path, string $filename)
    {
        // Berkas ada di disk privat; BerkasService masih membaca disk public untuk
        // berkas lama yang belum dipindahkan (`php artisan berkas:amankan`).
        $fullPath = app(BerkasService::class)->pathFisik($path);

        if ($fullPath === null) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->file($fullPath, [
            // Nama berkas berasal dari input pengguna — buang karakter yang merusak header.
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[\\\\\/"\r\n]+/', '_', $filename) . '"',
            // Berkas privat: jangan disimpan cache bersama/proxy.
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Hak unduh = hak lihat permohonan. Portal internal memakai PermohonanPolicy::view
     * (sumber kebenaran yang sama dengan halaman detail), sehingga siapa pun yang boleh
     * membuka detail juga bisa membuka berkasnya. Portal pemohon: hanya milik sendiri.
     */
    private function canAccessPermohonan(\App\Models\Permohonan $permohonan): bool
    {
        if (request()->routeIs('pemohon.*')) {
            $pbf = auth('pemohon')->user();

            return $pbf !== null && $permohonan->pbf_id === $pbf->id;
        }

        $user = auth('web')->user();

        return $user !== null && Gate::forUser($user)->allows('view', $permohonan);
    }
}
