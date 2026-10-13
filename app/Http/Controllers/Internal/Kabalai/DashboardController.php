<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Models\SuratPengesahan;
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
        $dateFormatPengajuan = $driver === 'sqlite'
            ? "strftime('%Y-%m', tanggal_pengajuan)"
            : "DATE_FORMAT(tanggal_pengajuan, '%Y-%m')";
        $dateFormatTerbit = $driver === 'sqlite'
            ? "strftime('%Y-%m', tanggal_upload)"
            : "DATE_FORMAT(tanggal_upload, '%Y-%m')";
        $dateFormatLog = $driver === 'sqlite'
            ? "strftime('%Y-%m', waktu_mulai)"
            : "DATE_FORMAT(waktu_mulai, '%Y-%m')";

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

        // Statistik bulanan: Pengajuan dihitung dari tanggal_pengajuan, Terbit dihitung dari tanggal terbit surat
        $pengajuanPerBulan = Permohonan::query()
            ->whereYear('tanggal_pengajuan', $year)
            ->selectRaw("{$dateFormatPengajuan} as bulan, COUNT(*) as total")
            ->groupByRaw($dateFormatPengajuan)
            ->pluck('total', 'bulan');

        $terbitPerBulan = SuratPengesahan::query()
            ->whereHas('permohonan')
            ->whereYear('tanggal_upload', $year)
            ->selectRaw("{$dateFormatTerbit} as bulan, COUNT(*) as terbit")
            ->groupByRaw($dateFormatTerbit)
            ->pluck('terbit', 'bulan');

        $ditutupPerBulan = StatusLog::query()
            ->whereHas('permohonan')
            ->where('status', Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)
            ->whereYear('waktu_mulai', $year)
            ->selectRaw("{$dateFormatLog} as bulan, COUNT(*) as ditutup")
            ->groupByRaw($dateFormatLog)
            ->pluck('ditutup', 'bulan');

        $semuaBulan = $pengajuanPerBulan->keys()
            ->merge($terbitPerBulan->keys())
            ->merge($ditutupPerBulan->keys())
            ->unique()
            ->sort()
            ->values();

        $statBulanan = $semuaBulan->map(function ($bln) use ($pengajuanPerBulan, $terbitPerBulan, $ditutupPerBulan) {
            return (object) [
                'bulan' => $bln,
                'total' => (int) ($pengajuanPerBulan[$bln] ?? 0),
                'terbit' => (int) ($terbitPerBulan[$bln] ?? 0),
                'ditutup' => (int) ($ditutupPerBulan[$bln] ?? 0),
            ];
        });

        // Available years (from earliest record to current year)
        $yearExprPengajuan = $driver === 'sqlite'
            ? "CAST(strftime('%Y', tanggal_pengajuan) AS INTEGER)"
            : 'YEAR(tanggal_pengajuan)';
        $yearExprTerbit = $driver === 'sqlite'
            ? "CAST(strftime('%Y', tanggal_upload) AS INTEGER)"
            : 'YEAR(tanggal_upload)';

        $yearsPengajuan = Permohonan::query()
            ->selectRaw("{$yearExprPengajuan} as year")
            ->distinct()
            ->pluck('year');

        $yearsTerbit = SuratPengesahan::query()
            ->whereHas('permohonan')
            ->selectRaw("{$yearExprTerbit} as year")
            ->distinct()
            ->pluck('year');

        $availableYears = $yearsPengajuan->merge($yearsTerbit)
            ->filter()
            ->unique()
            ->sortDesc()
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
