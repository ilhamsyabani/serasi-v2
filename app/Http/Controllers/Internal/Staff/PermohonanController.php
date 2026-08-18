<?php

namespace App\Http\Controllers\Internal\Staff;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Daftar permohonan yang ditugaskan ke Staff Sertifikasi.
 * Mendukung filter: belum dievaluasi, sudah dievaluasi, terbit.
 */
class PermohonanController extends Controller
{
    private const FILTER_BELUM = ['didisposisikan', 'proses_evaluasi'];
    private const FILTER_SUDAH = [
        'revisi_1', 'revisi_2', 'revisi_3',
        'ditutup_pengajuan_ulang',
        'menunggu_surat_pengesahan',
    ];
    private const FILTER_TERBIT = ['terbit_surat_pengesahan'];

    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->get('filter', 'belum_evaluasi');
        $search = $request->input('search');

        $query = Permohonan::query()
            ->whereHas('distribusiAktif', fn($q) => $q->where('staff_id', $user->id))
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff', 'revisi.dokumenRevisi']);

        // Filter kategori
        $statuses = match ($filter) {
            'belum_evaluasi' => self::FILTER_BELUM,
            'sudah_evaluasi' => self::FILTER_SUDAH,
            'terbit' => self::FILTER_TERBIT,
            default => self::FILTER_BELUM,
        };
        $query->whereIn('status_saat_ini', $statuses);

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pbf_snapshot', 'like', "%{$search}%")
                    ->orWhere('no_registrasi', 'like', "%{$search}%")
                    ->orWhere('nib_snapshot', 'like', "%{$search}%");
            });
        }

        $permohonans = $query->latest()->get();

        // Count badge untuk setiap filter
        $baseQuery = Permohonan::query()
            ->whereHas('distribusiAktif', fn($q) => $q->where('staff_id', $user->id));

        $counts = [
            'belum_evaluasi' => (clone $baseQuery)->whereIn('status_saat_ini', self::FILTER_BELUM)->count(),
            'sudah_evaluasi' => (clone $baseQuery)->whereIn('status_saat_ini', self::FILTER_SUDAH)->count(),
            'terbit' => (clone $baseQuery)->whereIn('status_saat_ini', self::FILTER_TERBIT)->count(),
        ];

        return view('internal.staff.permohonan.index', [
            'permohonans' => $permohonans,
            'filter' => $filter,
            'counts' => $counts,
            'search' => $search,
        ]);
    }
}
