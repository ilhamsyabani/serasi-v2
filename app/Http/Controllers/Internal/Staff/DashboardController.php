<?php

namespace App\Http\Controllers\Internal\Staff;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use App\Models\Evaluasi;
use App\Models\Permohonan;
use App\Models\Revisi;
use App\Services\NotifikasiService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search');

        $query = Permohonan::whereHas('distribusiAktif', fn($q) => $q->where('staff_id', $user->id))
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pbf_snapshot', 'like', "%{$search}%")
                ->orWhere('no_registrasi', 'like', "%{$search}%")
                ->orWhere('nib_snapshot', 'like', "%{$search}%");
            });
        }

        $permohonans = $query->latest()->get();

        return view('internal.staff.dashboard', compact('user', 'permohonans', 'search'));
    }
}
