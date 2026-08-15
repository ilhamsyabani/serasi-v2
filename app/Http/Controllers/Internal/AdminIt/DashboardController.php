<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\SlaCalculator;

class DashboardController extends Controller
{
    public function index(SlaCalculator $sla)
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalPbf' => Pbf::count(),
            'totalPermohonan' => Permohonan::count(),
            'notifikasiGagal' => Notifikasi::where('status_kirim', 'gagal')->count(),
        ];

        $permohonans = Permohonan::query()
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->latest()
            ->get();

        return view('internal.adminit.dashboard', [
            'stats' => $stats,
            'permohonans' => $permohonans,
            'slaRingkasan' => $sla->ringkasan($permohonans),
        ]);
    }
}
