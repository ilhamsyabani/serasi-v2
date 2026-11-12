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
use Illuminate\Support\Facades\DB;

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
        
        // Termasuk pengajuan ulang mandiri pemohon (kepala_balai_id NULL) — tanpa ini
        // pengajuan ulang tidak pernah muncul di daftar disposisi dan macet selamanya.
        $query = Permohonan::where('status_saat_ini', Permohonan::STATUS_PENGAJUAN)
            ->untukKepalaBalai($user->id);

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
        $ketuaTimList = User::whereHas('role', fn($q) => $q->where('kode', 'ketua_tim'))->where('is_aktif', true)->orderBy('nama')->get();
        return view('internal.kabalai.disposisi.index', compact('permohonans', 'ketuaTimList', 'sort', 'dir', 'tanggalDari', 'tanggalSampai', 'search'));
    }

    public function store(Request $request, Permohonan $permohonan)
    {
        // Milik Kepala Balai ini, atau pengajuan ulang mandiri pemohon (belum punya pemilik).
        abort_unless($permohonan->kepala_balai_id === null || $permohonan->kepala_balai_id === Auth::id(), 403);

        $data = $request->validate([
            'ketua_tim_id' => 'required|integer',
            'catatan' => 'nullable|string',
        ]);

        // Tujuan disposisi wajib Ketua Tim yang aktif.
        $ketuaTim = User::whereKey($data['ketua_tim_id'])
            ->where('is_aktif', true)
            ->whereHas('role', fn ($q) => $q->where('kode', 'ketua_tim'))
            ->first();

        if (! $ketuaTim) {
            return back()->withErrors(['ketua_tim_id' => 'Ketua Tim yang dipilih tidak valid atau tidak aktif.'])->withInput();
        }

        $berhasil = DB::transaction(function () use ($permohonan, $ketuaTim, $data) {
            $terkunci = Permohonan::whereKey($permohonan->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->status_saat_ini !== Permohonan::STATUS_PENGAJUAN || $terkunci->disposisi) {
                return false;
            }

            Disposisi::create([
                'permohonan_id' => $terkunci->id,
                'kepala_balai_id' => Auth::id(),
                'ketua_tim_id' => $ketuaTim->id,
                'catatan' => $data['catatan'] ?? null,
                'tanggal_disposisi' => now(),
            ]);

            app(StatusTransitionService::class)->transisi($terkunci, Permohonan::STATUS_DIDISPOSISIKAN, 'Didisposisikan ke Ketua Tim', Auth::user(), 'internal');

            return true;
        });

        if (! $berhasil) {
            return back()->with('error', 'Permohonan sudah didisposisikan.');
        }

        $permohonan->refresh();

        $notif = app(NotifikasiService::class);
        $notif->kirimNotifikasiKetuaTim($ketuaTim, $permohonan, 'DISPOSISI_BARU');
        $notif->kirim($permohonan, Notifikasi::TUJUAN_KETUA_TIM, $ketuaTim->id, Notifikasi::CHANNEL_EMAIL, 'DISPOSISI_BARU');

        return back()->with('success', 'Disposisi berhasil dikirim.');
    }
}
