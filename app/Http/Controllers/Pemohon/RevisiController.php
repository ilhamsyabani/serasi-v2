<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\DokumenRevisi;
use App\Models\Permohonan;
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

        $revisi = \App\Models\Revisi::create([
            'evaluasi_id' => $evaluasiTerakhir->id,
            'permohonan_id' => $permohonan->id,
        ]);

        foreach ($request->file('dokumen') as $file) {
            $path = $file->store('dokumen_revisi', 'public');
            DokumenRevisi::create([
                'revisi_id' => $revisi->id,
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => $path,
                'uploaded_at' => now(),
            ]);
        }

        app(NotifikasiService::class)->kirim($permohonan, 'staff', $permohonan->distribusiAktif->staff_id ?? 0, 'email', 'REVISI_DITERIMA');
        app(\App\Services\StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_PROSES_EVALUASI, 'Revisi diupload', null, 'pemohon');

        return redirect()->route('pemohon.dashboard')->with('success', 'Revisi berhasil dikirim.');
    }
}
