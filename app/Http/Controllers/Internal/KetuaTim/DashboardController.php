<?php

namespace App\Http\Controllers\Internal\KetuaTim;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\SlaCalculator;
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

    public function index(SlaCalculator $sla)
    {
        $user = Auth::guard('web')->user();

        $permohonans = Permohonan::query()
            ->whereHas('disposisi', fn ($q) => $q->where('ketua_tim_id', $user->id))
            ->whereIn('status_saat_ini', self::STATUS_AKTIF)
            // Eager load: timeline tiap baris membaca statusLog + aktor; tanpa ini N+1.
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->latest()
            ->get();

        $perluDistribusi = $permohonans->where('status_saat_ini', Permohonan::STATUS_DIDISPOSISIKAN);

        $staffList = User::whereHas('role', fn ($q) => $q->where('kode', 'staff_sertifikasi'))
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        // Beban kerja = jumlah permohonan aktif tim ini yang sedang dipegang tiap staff.
        $bebanKerja = $permohonans
            ->groupBy(fn ($p) => $p->distribusiAktif?->staff_id)
            ->map->count();

        return view('internal.ketua_tim.dashboard', [
            'user' => $user,
            'permohonans' => $permohonans,
            'perluDistribusi' => $perluDistribusi,
            'staffList' => $staffList,
            'bebanKerja' => $bebanKerja,
            'slaRingkasan' => $sla->ringkasan($permohonans),
        ]);
    }
}
