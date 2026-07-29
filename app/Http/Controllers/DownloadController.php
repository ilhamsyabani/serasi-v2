<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

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
        $dokumen = \App\Models\DokumenRevisi::where('revisi_id', $revisiId)
            ->firstOrFail();

        abort_unless(
            $this->canAccessPermohonan($dokumen->revisi->permohonan),
            403
        );

        return $this->previewFile($dokumen->path_file, $dokumen->nama_file_asli);
    }

    public function surat(int $permohonanId)
    {
        $surat = \App\Models\SuratPengesahan::where('permohonan_id', $permohonanId)
            ->firstOrFail();

        abort_unless(
            $this->canAccessPermohonan($surat->permohonan),
            403
        );

        return $this->previewFile($surat->path_file, $surat->nomor_surat . '.pdf');
    }

    private function previewFile(string $path, string $filename)
    {
        if (! Storage::disk('public')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $disk = Storage::disk('public');
        $fullPath = $disk->path($path);

        $response = response()->file($fullPath, [
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);

        return $response;
    }

    private function canAccessPermohonan(\App\Models\Permohonan $permohonan): bool
    {
        $user = auth()->user();

        if ($user && $user->isAdminIt()) {
            return true;
        }

        if ($user && $user->isKepalaBalai()) {
            return $permohonan->kepala_balai_id === $user->id;
        }

        if ($user && $user->isStaffSertifikasi()) {
            return $permohonan->distribusiAktif?->staff_id === $user->id;
        }

        if ($user && $user->isKetuaTim()) {
            return $permohonan->disposisi?->tim_id === $user->tim_id;
        }

        $pbf = auth('pbf')->user();
        if ($pbf) {
            return $permohonan->pbf_id === $pbf->id;
        }

        return false;
    }
}
