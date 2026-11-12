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
use Illuminate\Support\Facades\DB;

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

        // Ketua Tim hanya boleh mendistribusikan permohonan yang didisposisikan kepadanya.
        abort_unless($permohonan->disposisi?->ketua_tim_id === $user->id, 403, 'Permohonan ini tidak didisposisikan kepada tim Anda.');

        $data = $request->validate([
            'staff_id' => 'required|integer',
            'catatan' => 'nullable|string',
        ]);

        // Penerima wajib Staff Sertifikasi yang aktif — bukan sembarang user.
        $staff = User::whereKey($data['staff_id'])
            ->where('is_aktif', true)
            ->whereHas('role', fn ($q) => $q->where('kode', 'staff_sertifikasi'))
            ->first();

        if (! $staff) {
            return back()->withErrors(['staff_id' => 'Staff yang dipilih tidak valid atau tidak aktif.'])->withInput();
        }

        $berhasil = DB::transaction(function () use ($permohonan, $user, $staff) {
            $terkunci = Permohonan::whereKey($permohonan->id)->lockForUpdate()->firstOrFail();

            // Distribusi awal hanya dari status `didisposisikan`. Tanpa cek ini, kiriman
            // ulang bisa menarik permohonan yang sudah berjalan/terbit kembali ke evaluasi.
            if ($terkunci->status_saat_ini !== Permohonan::STATUS_DIDISPOSISIKAN) {
                return false;
            }

            // 1 permohonan = 1 staff aktif: nonaktifkan baris lama (riwayat tetap ada).
            Distribusi::where('permohonan_id', $terkunci->id)->where('is_aktif', true)->update(['is_aktif' => false]);

            Distribusi::create([
                'permohonan_id' => $terkunci->id,
                'ketua_tim_id' => $user->id,
                'staff_id' => $staff->id,
                'jenis' => 'distribusi_awal',
                'is_aktif' => true,
                'tanggal' => now(),
            ]);

            app(StatusTransitionService::class)->transisi($terkunci, Permohonan::STATUS_PROSES_EVALUASI, 'Didistribusikan ke Staff', $user, 'internal');

            return true;
        });

        if (! $berhasil) {
            return back()->with('error', 'Permohonan ini sudah didistribusikan atau tidak lagi menunggu distribusi.');
        }

        $permohonan->refresh();
        $data['staff_id'] = $staff->id;

        app(NotifikasiService::class)->kirim($permohonan, Notifikasi::TUJUAN_STAFF, $data['staff_id'], Notifikasi::CHANNEL_EMAIL, 'DISTRIBUSI_BARU');

        app(NotifikasiService::class)->kirimNotifikasiStaff($staff, $permohonan, 'DISTRIBUSI_BARU');

        // Konfirmasi ke KT bahwa staff sudah ditugaskan
        app(NotifikasiService::class)->kirimNotifikasiKetuaTim($user, $permohonan, 'DISTRIBUSI_BARU');

        return back()->with('success', 'Permohonan berhasil didistribusikan.');
    }
}
