<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\SlaCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Kepala Balai — pengawasan (oversight) view-only atas SELURUH
 * permohonan balai, termasuk pengajuan ulang mandiri pemohon (kepala_balai_id NULL).
 * Kepala Balai tidak punya tombol aksi apa pun di sini (CLAUDE.md §3 poin 8);
 * satu-satunya aksi miliknya (input permohonan baru) ada di menu Permohonan.
 */
class DashboardController extends Controller
{
    public function index(SlaCalculator $sla)
    {
        $permohonans = Permohonan::query()
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->latest()
            ->get();

        $statBulanan = Permohonan::query()
            ->selectRaw("DATE_FORMAT(tanggal_pengajuan, '%Y-%m') as bulan")
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status_saat_ini = 'terbit_surat_pengesahan' THEN 1 ELSE 0 END) as terbit")
            ->selectRaw("SUM(CASE WHEN status_saat_ini = 'ditutup_pengajuan_ulang' THEN 1 ELSE 0 END) as ditutup")
            ->whereYear('tanggal_pengajuan', now()->year)
            ->groupByRaw("DATE_FORMAT(tanggal_pengajuan, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(tanggal_pengajuan, '%Y-%m')")
            ->get();

        $onProcess = $permohonans->whereNotIn('status_saat_ini', ['terbit_surat_pengesahan', 'ditutup_pengajuan_ulang'])->count();

        return view('internal.kabalai.dashboard', [
            'permohonans' => $permohonans,
            'slaRingkasan' => $sla->ringkasan($permohonans),
            'statBulanan' => $statBulanan,
            'onProcess' => $onProcess,
        ]);
    }
}
