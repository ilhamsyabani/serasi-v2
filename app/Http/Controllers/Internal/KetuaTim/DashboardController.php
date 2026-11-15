<?php

namespace App\Http\Controllers\Internal\KetuaTim;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\SlaCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard terpadu Ketua Tim (M-17).
 *
 * Semua yang dibutuhkan Katim ada di satu halaman: ringkasan beban kerja tim,
 * status SLA, dan daftar permohonan lengkap dengan aksi distribusi inline —
 * menggantikan pemisahan lama antara halaman Dashboard dan halaman Distribusi.
 */
class DashboardController extends Controller
{
    /** Status yang masih berjalan di bawah tanggung jawab Ketua Tim. */
    private const STATUS_AKTIF = [
        Permohonan::STATUS_DIDISPOSISIKAN,
        Permohonan::STATUS_PROSES_EVALUASI,
        Permohonan::STATUS_REVISI_1,
        Permohonan::STATUS_REVISI_2,
        Permohonan::STATUS_REVISI_3,
        Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
    ];

    public function index(Request $request, SlaCalculator $sla)
    {
        $user = Auth::guard('web')->user();
        $filter = $request->only(['search', 'status', 'tanggal_dari', 'tanggal_sampai']);

        // Stats: semua permohonan (tanpa filter)
        $allPermohonans = Permohonan::query()
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->latest()
            ->get();

        $timQuery = fn () => Permohonan::query()
            ->whereHas('disposisi', fn ($q) => $q->where('ketua_tim_id', $user->id));

        // Badge & beban kerja dihitung dari seluruh permohonan tim, bukan hanya halaman tabel aktif.
        $permohonanTim = $timQuery()->with('distribusiAktif')->get();

        $permohonans = $timQuery()
            ->filterTabel($filter)
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $perluDistribusi = $permohonanTim->where('status_saat_ini', Permohonan::STATUS_DIDISPOSISIKAN);

        $staffList = User::whereHas('role', fn ($q) => $q->where('kode', 'staff_sertifikasi'))
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        // Beban kerja = jumlah permohonan aktif tim ini yang sedang dipegang tiap staff.
        $bebanKerja = $permohonanTim
            ->groupBy(fn ($p) => $p->distribusiAktif?->staff_id)
            ->map->count();

        return view('internal.ketua_tim.dashboard', [
            'user' => $user,
            'permohonans' => $permohonans,
            'allPermohonans' => $allPermohonans,
            'perluDistribusi' => $perluDistribusi,
            'staffList' => $staffList,
            'bebanKerja' => $bebanKerja,
            'filter' => $filter,
            'slaRingkasan' => $sla->ringkasan($allPermohonans),
        ]);
    }
}
