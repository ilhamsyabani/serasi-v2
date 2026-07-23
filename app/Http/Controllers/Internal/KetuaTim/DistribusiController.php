<?php

namespace App\Http\Controllers\Internal\KetuaTim;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use App\Models\Permohonan;
use App\Models\ReassignmentLog;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DistribusiController extends Controller
{
    /**
     * Halaman khusus distribusi: daftar permohonan tim ini yang berstatus
     * `didisposisikan` (menunggu ditugaskan ke staff), lengkap dengan beban
     * kerja tiap staff sebagai bahan pertimbangan penugasan.
     */
    public function index()
    {
        $user = Auth::user();

        $permohonans = Permohonan::query()
            ->whereHas('disposisi', fn ($q) => $q->where('ketua_tim_id', $user->id))
            ->where('status_saat_ini', Permohonan::STATUS_DIDISPOSISIKAN)
            ->with(['statusLog', 'disposisi.ketuaTim'])
            ->latest()
            ->get();

        $staffList = User::whereHas('role', fn ($q) => $q->where('kode', 'staff_sertifikasi'))
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        // Beban kerja = permohonan aktif (belum final) yang sedang dipegang tiap staff.
        $bebanKerja = Distribusi::query()
            ->where('is_aktif', true)
            ->whereHas('permohonan', fn ($q) => $q->whereNotIn('status_saat_ini', [
                Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN,
                Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG,
            ]))
            ->selectRaw('staff_id, COUNT(*) as total')
            ->groupBy('staff_id')
            ->pluck('total', 'staff_id');

        return view('internal.ketua_tim.distribusi.index', compact('permohonans', 'staffList', 'bebanKerja'));
    }

    public function store(Request $request, Permohonan $permohonan)
    {
        $user = Auth::user();
        if ($user->role->kode !== 'ketua_tim') {
            abort(403);
        }

        $data = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string',
        ]);

        Distribusi::create([
            'permohonan_id' => $permohonan->id,
            'ketua_tim_id' => $user->id,
            'staff_id' => $data['staff_id'],
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => now(),
        ]);

        app(StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_PROSES_EVALUASI, 'Didistribusikan ke Staff', $user, 'internal');

        app(NotifikasiService::class)->kirim($permohonan, 'staff', $data['staff_id'], 'email', 'DISTRIBUSI_BARU');

        return back()->with('success', 'Permohonan berhasil didistribusikan.');
    }
}
