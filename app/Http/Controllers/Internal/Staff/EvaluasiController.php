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
    public const STATUS_BISA_DIEVALUASI = [
        Permohonan::STATUS_PROSES_EVALUASI,
        Permohonan::STATUS_DIDISPOSISIKAN,
        Permohonan::STATUS_REVISI_1,
        Permohonan::STATUS_REVISI_2,
        Permohonan::STATUS_REVISI_3,
    ];

    private function canEvaluate(Permohonan $permohonan): bool
    {
        return in_array($permohonan->status_saat_ini, self::STATUS_BISA_DIEVALUASI, true);
    }

    public function edit(Permohonan $permohonan)
    {
        $user = Auth::user();
        $aktif = $permohonan->distribusiAktif;
        abort_if(!$aktif || $aktif->staff_id !== $user->id, 403);
        abort_if(!$this->canEvaluate($permohonan), 403, 'Permohonan tidak dalam tahap proses evaluasi.');

        $dokumen = DokumenPermohonan::where('permohonan_id', $permohonan->id)->get();
        $evaluasi = Evaluasi::where('permohonan_id', $permohonan->id)->latest()->first();

        $revisi = $permohonan->revisi()->with('dokumenRevisi')->get();
        $dokumenRevisi = $revisi->flatMap(fn($r) => $r->dokumenRevisi);

        return view('internal.staff.evaluasi.edit', compact('permohonan', 'dokumen', 'dokumenRevisi', 'evaluasi'));
    }

    public function update(Request $request, Permohonan $permohonan)
    {
        $user = Auth::user();
        $aktif = $permohonan->distribusiAktif;
        abort_if(!$aktif || $aktif->staff_id !== $user->id, 403);
        abort_if(!$this->canEvaluate($permohonan), 422, 'Permohonan tidak dalam tahap proses evaluasi.');

        $data = $request->validate([
            'hasil' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
            'siklus_ke' => 'nullable|integer',
        ]);

        $siklus = (Evaluasi::where('permohonan_id', $permohonan->id)->max('siklus_ke') ?? 0) + 1;

        if ($request->filled('siklus_ke') && (int) $request->siklus_ke !== $siklus) {
            abort(422, 'Evaluasi untuk siklus ini sudah pernah diproses.');
        }

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
