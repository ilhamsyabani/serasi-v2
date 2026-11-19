<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\KoreksiStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Koreksi status oleh Admin IT: kembalikan permohonan ke revisi berikutnya.
 * Logika & penjaga ada di KoreksiStatusService, bukan di sini.
 */
class KoreksiStatusController extends Controller
{
    public function index(Request $request, KoreksiStatusService $koreksi)
    {
        $cari = trim((string) $request->query('search'));

        $permohonan = Permohonan::query()
            ->whereIn('status_saat_ini', KoreksiStatusService::STATUS_BOLEH)
            ->with(['evaluasiTerakhir', 'distribusiAktif.staff'])
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('no_registrasi', 'like', "%$cari%")
                ->orWhere('nama_pbf_snapshot', 'like', "%$cari%")))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $permohonan->getCollection()->each(function (Permohonan $p) use ($koreksi) {
            $p->koreksi_tujuan = $koreksi->statusTujuan($p);
            $p->koreksi_tolak = $koreksi->alasanTidakBoleh($p);
        });

        return view('internal.adminit.koreksi-status.index', [
            'permohonan' => $permohonan,
            'maksRevisi' => Permohonan::MAKS_REVISI,
        ]);
    }

    public function store(Request $request, Permohonan $permohonan, KoreksiStatusService $koreksi)
    {
        Gate::authorize('koreksiStatus', $permohonan);

        $data = $request->validate([
            'catatan' => 'required|string|max:5000',
            'alasan' => 'required|string|max:1000',
            'kirim_notifikasi' => 'nullable|boolean',
        ], [
            'catatan.required' => 'Catatan ketidaksesuaian untuk pemohon wajib diisi.',
            'alasan.required' => 'Alasan koreksi wajib diisi.',
        ]);

        try {
            $log = $koreksi->kembalikanKeRevisi(
                $permohonan,
                $data['catatan'],
                $data['alasan'],
                Auth::user(),
                $request->boolean('kirim_notifikasi'),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = $log->status === Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG
            ? 'DITUTUP (kuota revisi habis)'
            : 'Revisi ke-' . $permohonan->fresh()->revisi_ke;

        return redirect()->route('internal.adminit.koreksi-status.index')
            ->with('success', "Permohonan {$permohonan->no_registrasi} dikembalikan: $label.");
    }
}
