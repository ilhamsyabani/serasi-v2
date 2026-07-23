<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalPbf' => Pbf::count(),
            'totalPermohonan' => Permohonan::count(),
            'notifikasiGagal' => Notifikasi::where('status_kirim', 'gagal')->count(),
        ];

        return view('internal.adminit.dashboard', compact('stats'));
    }
}
