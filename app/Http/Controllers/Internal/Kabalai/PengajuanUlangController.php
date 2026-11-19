<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use App\Services\PengajuanUlangService;
use App\Traits\ValidatesFileContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Menu "Pengajuan Ulang" Kepala Balai: daftar permohonan yang ditutup dan form
 * untuk mengajukannya ulang dengan akun PBF yang sama (cukup unggah dokumen).
 */
class PengajuanUlangController extends Controller
{
    use ValidatesFileContent;

    public function __construct(private PengajuanUlangService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('create', Permohonan::class);

        $tab = $request->get('tab') === 'sudah' ? 'sudah' : 'perlu';
        $search = trim((string) $request->get('search'));

        $query = ($tab === 'sudah' ? $this->service->querySudahDiajukanUlang() : $this->service->queryPerluPengajuanUlang())
            ->with(['pengajuanUlang', 'statusLog'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama_pbf_snapshot', 'like', "%{$search}%")
                ->orWhere('no_registrasi', 'like', "%{$search}%")
                ->orWhere('nib_snapshot', 'like', "%{$search}%")))
            ->orderByDesc('updated_at');

        $permohonans = $query->paginate(10)->withQueryString();

        return view('internal.kabalai.pengajuan_ulang.index', [
            'permohonans' => $permohonans,
            'tab' => $tab,
            'search' => $search,
            'jumlahPerlu' => $this->service->queryPerluPengajuanUlang()->count(),
            'service' => $this->service,
        ]);
    }

    public function create(Permohonan $permohonan)
    {
        Gate::authorize('ajukanUlang', $permohonan);

        return view('internal.kabalai.pengajuan_ulang.create', [
            'lama' => $permohonan->load('pbf'),
            'alasan' => $this->service->alasanDitutup($permohonan),
            'jumlahRevisi' => (int) $permohonan->revisi_ke,
        ]);
    }

    public function store(Request $request, Permohonan $permohonan)
    {
        Gate::authorize('ajukanUlang', $permohonan);

        $request->validate(DokumenPermohonan::aturanValidasi(wajib: false), [
            '*.mimes' => 'Berkas :attribute harus berformat ' . implode(', ', DokumenPermohonan::EKSTENSI_DIIZINKAN) . '.',
            '*.max' => 'Ukuran berkas :attribute maksimal ' . (DokumenPermohonan::UKURAN_MAKS_KB / 1024) . ' MB.',
        ], collect(DokumenPermohonan::JENIS)->map(fn ($m) => $m['label'])->all());

        $dokumen = collect(array_keys(DokumenPermohonan::JENIS))
            ->filter(fn ($jenis) => $request->hasFile($jenis))
            ->mapWithKeys(fn ($jenis) => [$jenis => $request->file($jenis)])
            ->all();

        // Pengajuan ulang harus membawa berkas perbaikan — minimal satu dokumen.
        if ($dokumen === []) {
            return back()->withErrors(['dokumen' => 'Unggah minimal satu dokumen perbaikan.']);
        }

        foreach ($dokumen as $file) {
            $this->assertAllowedFileMime($file);
        }

        try {
            $baru = $this->service->ajukanOlehKepalaBalai($permohonan, Auth::user(), $dokumen);
        } catch (\DomainException $e) {
            return redirect()->route('internal.kabalai.pengajuan-ulang.index')->with('error', $e->getMessage());
        }

        return redirect()->route('internal.kabalai.permohonan.show', $baru)
            ->with('success', "Pengajuan ulang {$baru->no_registrasi} dibuat dari {$permohonan->no_registrasi}. Lanjutkan dengan disposisi ke Ketua Tim.");
    }
}
