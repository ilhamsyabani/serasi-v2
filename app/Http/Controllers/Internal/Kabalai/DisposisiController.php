<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Disposisi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisposisiController extends Controller
{
    public function index(Request $request)
    {
        $sort   = $request->get('sort', 'tanggal_pengajuan');
        $dir    = $request->get('dir', 'desc');
        $tanggalDari   = $request->get('tanggal_dari', '');
        $tanggalSampai = $request->get('tanggal_sampai', '');
        $search = $request->get('search');
        $user = Auth::user();

        $allowedSorts = ['tanggal_pengajuan'];
        
        $query = Permohonan::where('status_saat_ini', Permohonan::STATUS_PENGAJUAN)
            ->where('kepala_balai_id', $user->id);

        if ($tanggalDari) {
            $query->whereDate('tanggal_pengajuan', '>=', $tanggalDari);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pbf_snapshot', 'like', "%{$search}%")
                ->orWhere('no_registrasi', 'like', "%{$search}%")
                ->orWhere('nib_snapshot', 'like', "%{$search}%");
            });
        }

        if ($tanggalSampai) {
            $query->whereDate('tanggal_pengajuan', '<=', $tanggalSampai);
        }

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $permohonans = $query->latest()->paginate(10);
        $ketuaTimList = User::whereHas('role', fn($q) => $q->where('kode', 'ketua_tim'))->get();
        return view('internal.kabalai.disposisi.index', compact('permohonans', 'ketuaTimList', 'sort', 'dir', 'tanggalDari', 'tanggalSampai', 'search'));
    }

    public function store(Request $request, Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);

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

        app(NotifikasiService::class)->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $data['ketua_tim_id'], Notifikasi::CHANNEL_WHATSAPP, 'DISPOSISI_BARU');

        return back()->with('success', 'Disposisi berhasil dikirim.');
    }
}
