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
        $search = $request->get('search');
        $status = $request->get('status');
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');

        // SQLite uses strftime, MySQL uses DATE_FORMAT
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $dateFormat = "strftime('%Y-%m', tanggal_pengajuan)";
        } else {
            $dateFormat = "DATE_FORMAT(tanggal_pengajuan, '%Y-%m')";
        }

        // Permohonan paginated + filtered
        $permohonans = Permohonan::query()
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->whereYear('tanggal_pengajuan', $year)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nama_pbf_snapshot', 'like', "%{$search}%")
                  ->orWhere('no_registrasi', 'like', "%{$search}%")
                  ->orWhere('nib_snapshot', 'like', "%{$search}%");
            }))
            ->when($status, fn ($q) => $q->where('status_saat_ini', $status))
            ->when($dari, fn ($q) => $q->whereDate('tanggal_pengajuan', '>=', $dari))
            ->when($sampai, fn ($q) => $q->whereDate('tanggal_pengajuan', '<=', $sampai))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Permohonan untuk statistik (filter tahun saja, tanpa filter search/status/tanggal)
        $allPerms = Permohonan::query()
            ->whereYear('tanggal_pengajuan', $year)
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->get();

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

        $statusOptions = [
            'pengajuan' => 'Pengajuan',
            'didisposisikan' => 'Didiposisisikan',
            'proses_evaluasi' => 'Proses Evaluasi',
            'revisi_1' => 'Revisi 1',
            'revisi_2' => 'Revisi 2',
            'revisi_3' => 'Revisi 3',
            'menunggu_surat_pengesahan' => 'Menunggu Surat',
            'terbit_surat_pengesahan' => 'Terbit Surat',
            'ditutup_pengajuan_ulang' => 'Ditutup',
        ];

        return view('internal.kabalai.dashboard', [
            'permohonans' => $permohonans,
            'allPermohonans' => $allPerms,
            'slaRingkasan' => $sla->ringkasan($allPerms),
            'statBulanan' => $statBulanan,
            'onProcess' => $onProcess,
            'selectedYear' => (int) $year,
            'availableYears' => $availableYears,
            'search' => $search,
            'statusFilter' => $status,
            'dari' => $dari,
            'sampai' => $sampai,
            'statusOptions' => $statusOptions,
        ]);
    }
}
