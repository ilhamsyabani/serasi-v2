<?php

namespace App\Http\Controllers\Internal\KetuaTim;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use App\Models\Notifikasi;
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
    public function index(Request $request)
    {
        $user = Auth::user();

        $sort   = $request->get('sort', 'tanggal_pengajuan');
        $dir    = $request->get('dir', 'desc');
        $tanggalDari   = $request->get('tanggal_dari', '');
        $tanggalSampai = $request->get('tanggal_sampai', '');
        $search = $request->get('search');
        $user = Auth::user();

        $allowedSorts = ['tanggal_pengajuan'];
        
        $query = Permohonan::query()
            ->whereHas('disposisi', fn ($q) => $q->where('ketua_tim_id', $user->id))
            ->where('status_saat_ini', Permohonan::STATUS_DIDISPOSISIKAN)
            ->with(['statusLog', 'disposisi.ketuaTim'])->latest();

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

        return view('internal.ketua_tim.distribusi.index', compact('permohonans', 'staffList', 'bebanKerja', 'sort', 'dir', 'tanggalDari', 'tanggalSampai', 'search'));
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

        app(NotifikasiService::class)->kirim($permohonan, Notifikasi::TUJUAN_STAFF, $data['staff_id'], Notifikasi::CHANNEL_EMAIL, 'DISTRIBUSI_BARU');

        $staff = User::find($data['staff_id']);
        app(NotifikasiService::class)->kirimNotifikasiStaff($staff, $permohonan, 'DISTRIBUSI_BARU');

        // Konfirmasi ke KT bahwa staff sudah ditugaskan
        app(NotifikasiService::class)->kirimNotifikasiKetuaTim($user, $permohonan, 'DISTRIBUSI_BARU');

        return back()->with('success', 'Permohonan berhasil didistribusikan.');
    }
}
