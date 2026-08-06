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

class RevisiController extends Controller
{
    use ValidatesFileContent;

    public function show(Permohonan $permohonan)
    {
        $pbf = Auth::guard('pemohon')->user();
        abort_if($permohonan->pbf_id !== $pbf->id, 403);
        abort_if(!in_array($permohonan->status_saat_ini, [Permohonan::STATUS_REVISI_1, Permohonan::STATUS_REVISI_2, Permohonan::STATUS_REVISI_3]), 403);

        $evaluasiTerakhir = $permohonan->evaluasiTerakhir;

        return view('pemohon.revisi.show', compact('permohonan', 'evaluasiTerakhir'));
    }

    public function store(Request $request, Permohonan $permohonan)
    {
        $pbf = Auth::guard('pemohon')->user();
        abort_if($permohonan->pbf_id !== $pbf->id, 403);
        abort_if(!in_array($permohonan->status_saat_ini, [Permohonan::STATUS_REVISI_1, Permohonan::STATUS_REVISI_2, Permohonan::STATUS_REVISI_3]), 403);

        $request->validate([
            'dokumen.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        foreach ($request->file('dokumen') as $file) {
            $this->assertAllowedFileMime($file);
        }

        $evaluasiTerakhir = $permohonan->evaluasiTerakhir;

        $revisi = Revisi::firstOrCreate(
            ['evaluasi_id' => $evaluasiTerakhir->id],
            ['permohonan_id' => $permohonan->id]
        );

        foreach ($request->file('dokumen') as $file) {
            $path = $file->store('dokumen_revisi', 'public');
            DokumenRevisi::create([
                'revisi_id' => $revisi->id,
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $path,
                'ukuran_file_kb' => round($file->getSize() / 1024, 2),
                'mime_type' => $file->getMimeType(),
                'uploaded_at' => now(),
            ]);
        }

        $staffId = $permohonan->distribusiAktif?->staff_id;
        $staff = $staffId ? User::find($staffId) : null;
        $notif = app(NotifikasiService::class);

        if ($staff) {
            $notif->kirim($permohonan, Notifikasi::TUJUAN_STAFF, $staffId, Notifikasi::CHANNEL_EMAIL, 'REVISI_DITERIMA');
            $notif->kirimNotifikasiStaff($staff, $permohonan, 'REVISI_DITERIMA');
        }

        // Notify KT bahwa pemohon telah upload revisi
        $ktId = $permohonan->disposisi?->ketua_tim_id;
        if ($ktId) {
            $kt = User::find($ktId);
            $notif->kirimNotifikasiKetuaTim($kt, $permohonan, 'REVISI_UPLOADED');
            $notif->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $ktId, Notifikasi::CHANNEL_EMAIL, 'REVISI_UPLOADED');
        }

        app(\App\Services\StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_PROSES_EVALUASI, 'Revisi diupload', null, 'pemohon');

        return redirect()->route('pemohon.dashboard')->with('success', 'Revisi berhasil dikirim.');
    }
}
