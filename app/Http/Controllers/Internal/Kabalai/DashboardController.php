<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\SlaCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Kepala Balai — pengawasan (oversight) view-only atas SELURUH
 * permohonan balai, termasuk pengajuan ulang mandiri pemohon (kepala_balai_id NULL).
 * Kepala Balai tidak punya tombol aksi apa pun di sini (CLAUDE.md §3 poin 8);
 * satu-satunya aksi miliknya (input permohonan baru) ada di menu Permohonan.
 */
class DashboardController extends Controller
{
    public function index(Request $request, SlaCalculator $sla)
    {
        $year = $request->get('tahun', now()->year);

        $permohonans = Permohonan::query()
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->latest()
            ->get();

        // SQLite uses strftime, MySQL uses DATE_FORMAT
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $dateFormat = "strftime('%Y-%m', tanggal_pengajuan)";
        } else {
            $dateFormat = "DATE_FORMAT(tanggal_pengajuan, '%Y-%m')";
        }

        $statBulanan = Permohonan::query()
            ->selectRaw("{$dateFormat} as bulan")
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN status_saat_ini = 'terbit_surat_pengesahan' THEN 1 ELSE 0 END) as terbit")
            ->selectRaw("SUM(CASE WHEN status_saat_ini = 'ditutup_pengajuan_ulang' THEN 1 ELSE 0 END) as ditutup")
            ->whereYear('tanggal_pengajuan', $year)
            ->groupByRaw($dateFormat)
            ->orderByRaw($dateFormat)
            ->get();

        // Available years (from earliest record to current year)
        $yearExpr = $driver === 'sqlite'
            ? "CAST(strftime('%Y', tanggal_pengajuan) AS INTEGER)"
            : 'YEAR(tanggal_pengajuan)';

        $availableYears = Permohonan::query()
            ->selectRaw("{$yearExpr} as year")
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($availableYears->isEmpty() || !$availableYears->contains(now()->year)) {
            $availableYears = $availableYears->push(now()->year)->sortDesc()->values();
        }

        $onProcess = Permohonan::whereYear('tanggal_pengajuan', $year)
            ->whereNotIn('status_saat_ini', ['terbit_surat_pengesahan', 'ditutup_pengajuan_ulang'])
            ->count();

        return view('internal.kabalai.dashboard', [
            'permohonans' => $permohonans,
            'slaRingkasan' => $sla->ringkasan($permohonans),
            'statBulanan' => $statBulanan,
            'onProcess' => $onProcess,
            'selectedYear' => (int) $year,
            'availableYears' => $availableYears,
        ]);
    }
}
