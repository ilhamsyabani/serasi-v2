<?php

namespace App\Http\Controllers\Internal\Staff;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\SuratPengesahan;
use App\Services\BerkasService;
use App\Services\NotifikasiService;
use App\Services\StatusTransitionService;
use App\Traits\ValidatesFileContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuratPengesahanController extends Controller
{
    use ValidatesFileContent;

    /** Hanya staff penanggung jawab aktif yang boleh menerbitkan surat (1 permohonan = 1 staff). */
    private function pastikanPenanggungJawab(Permohonan $permohonan): void
    {
        abort_unless(
            $permohonan->distribusiAktif?->staff_id === Auth::id(),
            403,
            'Permohonan ini tidak ditugaskan kepada Anda.'
        );
    }

    public function edit(Permohonan $permohonan)
    {
        $this->pastikanPenanggungJawab($permohonan);
        abort_if($permohonan->status_saat_ini !== Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, 403, 'Permohonan tidak dalam tahap menunggu surat pengesahan.');

        return view('internal.staff.surat.edit', compact('permohonan'));
    }

    public function update(Request $request, Permohonan $permohonan)
    {
        $this->pastikanPenanggungJawab($permohonan);

        $request->validate([
            'file_surat' => 'required|file|mimes:pdf|max:10240',
            'nomor_surat' => 'required|string|max:100',
        ]);
        $file = $request->file('file_surat');

        // Surat pengesahan wajib PDF — periksa isi berkas, bukan hanya ekstensinya.
        $this->assertAllowedFileMime($file, ['application/pdf']);

        $berkas = app(BerkasService::class);
        $path = $berkas->simpan($file, 'surat_pengesahan');

        try {
            $terbit = DB::transaction(function () use ($permohonan, $request, $file, $path) {
                // Kunci baris agar dua submit bersamaan tidak menerbitkan dua surat.
                $terkunci = Permohonan::whereKey($permohonan->id)->lockForUpdate()->firstOrFail();

                if ($terkunci->status_saat_ini !== Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN) {
                    return false;
                }

                SuratPengesahan::create([
                    'permohonan_id' => $terkunci->id,
                    'staff_id' => Auth::id(),
                    'path_file' => $path,
                    'nomor_surat' => $request->nomor_surat,
                    'tanggal_upload' => now(),
                    'nama_file_asli' => $file->getClientOriginalName(),
                ]);

                app(StatusTransitionService::class)->transisi($terkunci, Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN, 'Surat pengesahan diterbitkan', Auth::user(), 'internal');

                return true;
            });
        } catch (\Throwable $e) {
            $berkas->hapus($path);
            throw $e;
        }

        if (! $terbit) {
            $berkas->hapus($path);

            return redirect()->route(Auth::user()->ruteKembaliEvaluator())
                ->with('error', 'Surat tidak disimpan: permohonan tidak lagi dalam tahap menunggu surat pengesahan.');
        }

        $permohonan->refresh();

        $notif = app(NotifikasiService::class);
        $notif->kirim($permohonan, Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_WHATSAPP, 'SURAT_TERBIT');
        $notif->kirim($permohonan, Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_EMAIL, 'SURAT_TERBIT');

        return redirect()->route(Auth::user()->ruteKembaliEvaluator())->with('success', 'Surat pengesahan berhasil diterbitkan.');
    }
}
