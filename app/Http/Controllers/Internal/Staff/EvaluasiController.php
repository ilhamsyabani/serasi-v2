<?php

namespace App\Http\Controllers\Internal\Staff;

use App\Http\Controllers\Controller;
use App\Models\DokumenPermohonan;
use App\Models\DokumenRevisi;
use App\Models\Evaluasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluasiController extends Controller
{
    public function edit(Permohonan $permohonan)
    {
        $user = Auth::user();
        $aktif = $permohonan->distribusiAktif;
        abort_if(!$aktif || $aktif->staff_id !== $user->id, 403);

        $dokumen = DokumenPermohonan::where('permohonan_id', $permohonan->id)->get();

        $revisi = $permohonan->revisi()->with('dokumenRevisi')->get();
        $dokumenRevisi = $revisi->flatMap(fn($r) => $r->dokumenRevisi);

        return view('internal.staff.evaluasi.edit', compact('permohonan', 'dokumen', 'dokumenRevisi'));
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
        $notif = app(NotifikasiService::class);
        $ketuaTimId = $permohonan->disposisi?->ketua_tim_id;
        $ketuaTim = $ketuaTimId ? User::find($ketuaTimId) : null;

        if ($data['hasil'] === 'lengkap') {
            $transisi->transisi($permohonan, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, 'Evaluasi lengkap', $user, 'internal');

            if ($ketuaTim) {
                $notif->kirimNotifikasiKetuaTim($ketuaTim, $permohonan, 'SIAP_TERBIT');
                $notif->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $ketuaTimId, Notifikasi::CHANNEL_EMAIL, 'SIAP_TERBIT');
            }

            $pesan = 'Evaluasi disimpan. Permohonan siap terbit surat.';
        } else {
            $log = $transisi->mintaRevisiAtauTutup($permohonan, $data['catatan'], $user, 'internal');

            if ($log->status === Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG) {
                $notif->kirim($permohonan, Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_WHATSAPP, 'DITUTUP_PENGAJUAN_ULANG');
                $notif->kirim($permohonan, Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_EMAIL, 'DITUTUP_PENGAJUAN_ULANG');

                if ($ketuaTim) {
                    $notif->kirimNotifikasiKetuaTim($ketuaTim, $permohonan, 'DITUTUP_PENGAJUAN_ULANG');
                    $notif->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $ketuaTimId, Notifikasi::CHANNEL_EMAIL, 'DITUTUP_PENGAJUAN_ULANG');
                }

                $pesan = 'Kuota 3 revisi habis. Permohonan ditutup — pemohon perlu mengajukan ulang.';
            } else {
                $notif->kirim($permohonan, Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_WHATSAPP, 'REVISI_DIMINTA');
                $notif->kirim($permohonan, Notifikasi::TUJUAN_PEMOHON, $permohonan->pbf_id, Notifikasi::CHANNEL_EMAIL, 'REVISI_DIMINTA');

                if ($ketuaTim) {
                    $notif->kirimNotifikasiKetuaTim($ketuaTim, $permohonan, 'REVISI_DIMINTA');
                    $notif->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $ketuaTimId, Notifikasi::CHANNEL_EMAIL, 'REVISI_DIMINTA');
                }

                $pesan = 'Evaluasi disimpan. Permohonan dikembalikan untuk revisi.';
            }
        }

        return redirect()->route('internal.staff.dashboard')->with('success', $pesan);
    }
}
