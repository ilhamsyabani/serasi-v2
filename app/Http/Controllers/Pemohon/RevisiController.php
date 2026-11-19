<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\DokumenRevisi;
use App\Models\Revisi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Traits\ValidatesFileContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevisiController extends Controller
{
    use ValidatesFileContent;

    public function show(Permohonan $permohonan)
    {
        $pbf = Auth::guard('pemohon')->user();
        abort_if($permohonan->pbf_id !== $pbf->id, 403);
        abort_if(!in_array($permohonan->status_saat_ini, Permohonan::STATUS_REVISI), 403);

        $evaluasiTerakhir = $permohonan->evaluasiTerakhir;

        return view('pemohon.revisi.show', compact('permohonan', 'evaluasiTerakhir'));
    }

    public function store(Request $request, Permohonan $permohonan)
    {
        $pbf = Auth::guard('pemohon')->user();
        abort_if($permohonan->pbf_id !== $pbf->id, 403);
        abort_if(!in_array($permohonan->status_saat_ini, Permohonan::STATUS_REVISI), 403);

        $request->validate([
            // Tanpa aturan pada `dokumen` sendiri, form tanpa berkas lolos validasi.
            'dokumen' => 'required|array|min:1|max:10',
            'dokumen.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'dokumen.required' => 'Unggah minimal satu berkas perbaikan.',
            'dokumen.max' => 'Maksimal 10 berkas per pengiriman revisi.',
        ]);

        $files = $request->file('dokumen');
        foreach ($files as $file) {
            $this->assertAllowedFileMime($file);
        }

        $paths = [];

        try {
            DB::transaction(function () use ($permohonan, $files, &$paths) {
                // Kunci baris: dua kiriman bersamaan tidak boleh memicu dua transisi.
                $terkunci = Permohonan::whereKey($permohonan->id)->lockForUpdate()->firstOrFail();
                abort_if(!in_array($terkunci->status_saat_ini, Permohonan::STATUS_REVISI, true), 422, 'Revisi untuk permohonan ini sudah dikirim.');

                $evaluasiTerakhir = $terkunci->evaluasiTerakhir;
                abort_if(!$evaluasiTerakhir, 422, 'Catatan evaluasi untuk revisi ini tidak ditemukan.');

                $revisi = Revisi::firstOrCreate(
                    ['evaluasi_id' => $evaluasiTerakhir->id],
                    ['permohonan_id' => $terkunci->id]
                );

                foreach ($files as $file) {
                    $path = app(\App\Services\BerkasService::class)->simpan($file, 'dokumen_revisi');
                    $paths[] = $path;
                    DokumenRevisi::create([
                        'revisi_id' => $revisi->id,
                        'nama_file_asli' => $file->getClientOriginalName(),
                        'path_file' => $path,
                        'ukuran_file_kb' => round($file->getSize() / 1024, 2),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now(),
                    ]);
                }

                app(\App\Services\StatusTransitionService::class)->transisi($terkunci, Permohonan::STATUS_PROSES_EVALUASI, 'Revisi diupload', null, 'pemohon');
            });
        } catch (\Throwable $e) {
            app(\App\Services\BerkasService::class)->hapus($paths);
            throw $e;
        }

        $permohonan->refresh();

        // Notifikasi dikirim SETELAH data tersimpan, supaya staff tidak diberi tahu
        // tentang revisi yang ternyata gagal disimpan.
        $staffId = $permohonan->distribusiAktif?->staff_id;
        $staff = $staffId ? User::find($staffId) : null;
        $notif = app(NotifikasiService::class);

        if ($staff) {
            $notif->kirim($permohonan, Notifikasi::TUJUAN_STAFF, $staffId, Notifikasi::CHANNEL_EMAIL, 'REVISI_DITERIMA');
            $notif->kirimNotifikasiStaff($staff, $permohonan, 'REVISI_DITERIMA');
        }

        $ktId = $permohonan->disposisi?->ketua_tim_id;
        $kt = $ktId ? User::find($ktId) : null;
        // Ketua Tim yang juga evaluatornya sudah menerima REVISI_DITERIMA di atas — jangan dobel.
        if ($kt && $ktId !== $staffId) {
            $notif->kirimNotifikasiKetuaTim($kt, $permohonan, 'REVISI_UPLOADED');
            $notif->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $ktId, Notifikasi::CHANNEL_EMAIL, 'REVISI_UPLOADED');
        }

        return redirect()->route('pemohon.dashboard')->with('success', 'Revisi berhasil dikirim.');
    }
}
