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

        // Hanya permohonan yang ditugaskan ke staff ini — baik untuk kartu statistik
        // maupun tabel. Staff tidak melihat angka permohonan milik staff lain.
        $milikStaff = fn () => Permohonan::query()
            ->whereHas('distribusiAktif', fn ($q) => $q->where('staff_id', $user->id))
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff', 'revisi.dokumenRevisi']);

        // Stats: seluruh tugas staff ini, tidak terpengaruh filter tabel.
        $allPermohonans = $milikStaff()->latest()->get();

        // Table: tugas staff ini, dengan filter & pagination.
        $permohonans = $milikStaff()
            ->filterTabel($filter)
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
