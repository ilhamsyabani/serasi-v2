<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Disposisi;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisposisiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $permohonans = Permohonan::where('status_saat_ini', Permohonan::STATUS_PENGAJUAN)
            ->where('kepala_balai_id', $user->id)
            ->get();
        $ketuaTimList = User::whereHas('role', fn($q) => $q->where('kode', 'ketua_tim'))->get();
        return view('internal.kabalai.disposisi.index', compact('permohonans', 'ketuaTimList'));
    }

    public function store(Request $request, Permohonan $permohonan)
    {
        $data = $request->validate([
            'ketua_tim_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string',
        ]);

        if ($permohonan->disposisi) {
            return back()->with('error', 'Permohonan sudah didisposisikan.');
        }

        Disposisi::create([
            'permohonan_id' => $permohonan->id,
            'kepala_balai_id' => Auth::id(),
            'ketua_tim_id' => $data['ketua_tim_id'],
            'catatan' => $data['catatan'],
            'tanggal_disposisi' => now(),
        ]);

        app(StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_DIDISPOSISIKAN, 'Didisposisikan ke Ketua Tim', Auth::user(), 'internal');

        return back()->with('success', 'Disposisi berhasil dikirim.');
    }
}
