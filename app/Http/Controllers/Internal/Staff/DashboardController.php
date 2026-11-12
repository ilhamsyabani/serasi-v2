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
        $search = $request->input('search');

        // Hanya permohonan yang ditugaskan ke staff ini — baik untuk kartu statistik
        // maupun tabel. Staff tidak melihat angka permohonan milik staff lain.
        $milikStaff = fn () => Permohonan::query()
            ->whereHas('distribusiAktif', fn ($q) => $q->where('staff_id', $user->id))
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff', 'revisi.dokumenRevisi']);

        // Stats: seluruh tugas staff ini, tidak terpengaruh kotak pencarian.
        $allPermohonans = $milikStaff()->latest()->get();

        $query = $milikStaff();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pbf_snapshot', 'like', "%{$search}%")
                ->orWhere('no_registrasi', 'like', "%{$search}%")
                ->orWhere('nib_snapshot', 'like', "%{$search}%");
            });
        }

        $permohonans = $query->latest()->get();

        return view('internal.staff.dashboard', [
            'user' => $user,
            'permohonans' => $permohonans,
            'allPermohonans' => $allPermohonans,
            'search' => $search,
            'slaRingkasan' => $sla->ringkasan($allPermohonans),
        ]);
    }
}
