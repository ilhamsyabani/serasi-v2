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
    public function index()
    {
        $user = Auth::user();
        $permohonans = Permohonan::whereHas('distribusiAktif', fn($q) => $q->where('staff_id', $user->id))->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])->latest()->get();

        return view('internal.staff.dashboard', compact('user', 'permohonans'));
    }
}
