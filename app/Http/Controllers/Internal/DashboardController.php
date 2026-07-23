<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Dispatcher dashboard. Tidak punya view sendiri — mengarahkan tiap role ke
 * dashboard khususnya. Dipertahankan agar URL /admin/dashboard (dan redirect
 * pasca-login yang lama) tetap valid tanpa halaman generik.
 */
class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        $tujuan = match (true) {
            $user->isKepalaBalai() => 'internal.kabalai.dashboard',
            $user->isKetuaTim() => 'internal.ketua_tim.dashboard',
            $user->isStaffSertifikasi() => 'internal.staff.dashboard',
            $user->isAdminIt() => 'internal.adminit.dashboard',
            default => 'internal.login',
        };

        return redirect()->route($tujuan);
    }
}
