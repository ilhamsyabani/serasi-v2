<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $pbf = Auth::guard('pemohon')->user();
        $permohonanAktif = Permohonan::where('pbf_id', $pbf->id)->whereNotIn('status_saat_ini', [Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN, Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG])->latest()->first();
        $riwayat = Permohonan::where('pbf_id', $pbf->id)->orderByDesc('tanggal_pengajuan')->with('suratPengesahan')->get();

        return view('pemohon.dashboard', compact('pbf', 'permohonanAktif', 'riwayat'));
    }
}
