<?php

namespace App\Http\Controllers\Internal\Staff;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use App\Models\DokumenPermohonan;
use App\Models\Evaluasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Services\NotifikasiService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EvaluasiController extends Controller
{
    public function edit(Permohonan $permohonan)
    {
        $user = Auth::user();
        $aktif = $permohonan->distribusiAktif;
        abort_if(!$aktif || $aktif->staff_id !== $user->id, 403);

        $dokumen = DokumenPermohonan::where('permohonan_id', $permohonan->id)->get();

        return view('internal.staff.evaluasi.edit', compact('permohonan', 'dokumen'));
    }

    public function update(Request $request, Permohonan $permohonan)
    {
        $user = Auth::user();
        $aktif = $permohonan->distribusiAktif;
        abort_if(!$aktif || $aktif->staff_id !== $user->id, 403);

        $data = $request->validate([
            'hasil' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ]);

        $siklus = (Evaluasi::where('permohonan_id', $permohonan->id)->max('siklus_ke') ?? 0) + 1;

        Evaluasi::create([
            'permohonan_id' => $permohonan->id,
            'staff_id' => $user->id,
            'siklus_ke' => $siklus,
            'hasil' => $data['hasil'],
            'catatan' => $data['catatan'],
            'tanggal_evaluasi' => now(),
        ]);

        $transisi = app(StatusTransitionService::class);

        if ($data['hasil'] === 'lengkap') {
            $transisi->transisi($permohonan, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, 'Evaluasi lengkap', $user, 'internal');
            $pesan = 'Evaluasi disimpan. Permohonan siap terbit surat.';
        } else {
            $log = $transisi->mintaRevisiAtauTutup($permohonan, $data['catatan'], $user, 'internal');
            $pesan = $log->status === Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG
                ? 'Kuota 3 revisi habis. Permohonan ditutup — pemohon perlu mengajukan ulang.'
                : 'Evaluasi disimpan. Permohonan dikembalikan untuk revisi.';

            if ($log->status !== Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG) {
                app(NotifikasiService::class)->kirim($permohonan, Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_WHATSAPP, 'REVISI_DIMINTA');
            }
        }

        return redirect()->route('internal.staff.dashboard')->with('success', $pesan);
    }
}
