<?php

namespace App\Http\Controllers\Internal\Staff;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\SlaCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request, SlaCalculator $sla)
    {
        $user = Auth::user();
        $filter = $request->only(['search', 'status', 'tanggal_dari', 'tanggal_sampai']);

        // Stats: semua permohonan (tanpa filter user)
        $allPermohonans = Permohonan::query()
            ->latest()
            ->get();

        // Table: hanya permohonan yang ditugaskan ke staff ini
        $permohonans = Permohonan::query()
            ->whereHas('distribusiAktif', fn ($q) => $q->where('staff_id', $user->id))
            ->filterTabel($filter)
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff', 'revisi.dokumenRevisi'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('internal.staff.dashboard', [
            'user' => $user,
            'permohonans' => $permohonans,
            'allPermohonans' => $allPermohonans,
            'filter' => $filter,
            'slaRingkasan' => $sla->ringkasan($allPermohonans),
        ]);
    }
}
