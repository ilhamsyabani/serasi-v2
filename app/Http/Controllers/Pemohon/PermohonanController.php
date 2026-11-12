<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\StatusTransitionService;
use App\Traits\ValidatesFileContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PermohonanController extends Controller
{
    use ValidatesFileContent;

    public function index()
    {
        $pbf = Auth::guard('pemohon')->user();
        $permohonans = Permohonan::where('pbf_id', $pbf->id)->latest()->get();

        return view('pemohon.permohonan.index', compact('permohonans'));
    }

    public function show(Permohonan $permohonan)
    {
        $pbf = Auth::guard('pemohon')->user();
        abort_if($permohonan->pbf_id !== $pbf->id, 403);

        $permohonan->load('revisi.dokumenRevisi', 'revisi.evaluasi');

        return view('pemohon.permohonan.show', compact('permohonan'));
    }

    /**
     * Permohonan ditutup yang BELUM diajukan ulang. Satu permohonan ditutup hanya
     * boleh melahirkan satu pengajuan ulang — tanpa `whereDoesntHave`, pemohon bisa
     * membuat pengajuan ulang tak terbatas dari permohonan lama yang sama.
     */
    private function permohonanPerluPengajuanUlang(int $pbfId): ?Permohonan
    {
        return Permohonan::where('pbf_id', $pbfId)
            ->where('status_saat_ini', Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)
            ->whereDoesntHave('pengajuanUlang')
            ->latest()
            ->first();
    }

    public function create()
    {
        $pbf = Auth::guard('pemohon')->user();
        $parent = $this->permohonanPerluPengajuanUlang($pbf->id);
        abort_if(!$parent, 403, 'Tidak ada permohonan yang memerlukan pengajuan ulang.');

        return view('pemohon.permohonan.create', compact('parent'));
    }

    public function store(Request $request)
    {
        $pbf = Auth::guard('pemohon')->user();
        $parent = $this->permohonanPerluPengajuanUlang($pbf->id);
        abort_if(!$parent, 403, 'Tidak ada permohonan yang memerlukan pengajuan ulang.');

        $data = $request->validate([
            // Tanpa aturan pada `dokumen` sendiri, pengajuan ulang tanpa berkas lolos validasi.
            'dokumen' => 'required|array|min:1|max:5',
            'dokumen.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'dokumen.required' => 'Unggah dokumen persyaratan terlebih dahulu.',
            'dokumen.max' => 'Maksimal 5 berkas.',
        ]);

        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $file) {
                $this->assertAllowedFileMime($file);
            }
        }

        try {
            $noReg = Permohonan::generateNoRegistrasi();

            $permohonan = Permohonan::create([
                'no_registrasi' => $noReg,
                'pbf_id' => $pbf->id,
                'parent_permohonan_id' => $parent->id,
                'nama_pbf_snapshot' => $pbf->nama_pbf,
                'nib_snapshot' => $pbf->nib,
                'email_snapshot' => $pbf->email,
                'no_wa_snapshot' => $pbf->no_whatsapp,
                'status_saat_ini' => Permohonan::STATUS_PENGAJUAN,
                'tanggal_pengajuan' => now(),
                'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_PEMOHON,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000' || $e->getCode() === '23505') {
                \Illuminate\Support\Facades\Log::critical('Duplikat no_registrasi (pemohon): ' . ($noReg ?? 'unknown'), [
                    'pbf_id' => $pbf->id,
                    'parent_permohonan_id' => $parent->id,
                ]);
                return redirect()->back()
                    ->with('error', "Gagal menyimpan: No. Registrasi '{$noReg}' sudah terdaftar. Harap coba beberapa saat lagi.");
            }
            throw $e;
        }

        if ($request->hasFile('dokumen')) {
            $jenisDokumen = [
                'surat_permohonan',
                'surat_pernyataan',
                'rancangan_denah',
                'izin_pbf',
                'stra_pj',
            ];

            foreach ($request->file('dokumen') as $i => $file) {
                $path = app(\App\Services\BerkasService::class)->simpan($file, 'dokumen_permohonan');
                \App\Models\DokumenPermohonan::create([
                    'permohonan_id' => $permohonan->id,
                    'jenis_dokumen' => $jenisDokumen[$i] ?? 'dokumen_lain',
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => $path,
                    'ukuran_file_kb' => (int) ($file->getSize() / 1024),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by_pemohon_id' => $pbf->id,
                    'uploaded_at' => now(),
                ]);
            }
        }

        app(StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_PENGAJUAN, 'Pengajuan ulang', null, 'pemohon');

        return redirect()->route('pemohon.permohonan.show', $permohonan)->with('success', 'Permohonan berhasil diajukan ulang.');
    }
}
