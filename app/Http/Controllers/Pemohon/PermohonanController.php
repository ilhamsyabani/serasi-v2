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

        return view('pemohon.permohonan.show', compact('permohonan'));
    }

    public function create()
    {
        $pbf = Auth::guard('pemohon')->user();
        $parent = Permohonan::where('pbf_id', $pbf->id)->where('status_saat_ini', Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)->latest()->first();
        abort_if(!$parent, 403, 'Tidak ada permohonan yang memerlukan pengajuan ulang.');

        return view('pemohon.permohonan.create', compact('parent'));
    }

    public function store(Request $request)
    {
        $pbf = Auth::guard('pemohon')->user();
        $parent = Permohonan::where('pbf_id', $pbf->id)->where('status_saat_ini', Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)->latest()->first();
        abort_if(!$parent, 403);

        $data = $request->validate([
            'dokumen.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('dokumen')) {
            foreach ($request->file('dokumen') as $file) {
                $this->assertAllowedFileMime($file);
            }
        }

        $noReg = 'PBF/DENAH/' . date('Y') . '/' . str_pad(Permohonan::count() + 1, 5, '0', STR_PAD_LEFT);

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

        if ($request->hasFile('dokumen')) {
            $jenisDokumen = [
                'surat_permohonan',
                'surat_pernyataan',
                'rancangan_denah',
                'izin_pbf',
                'stra_pj',
            ];

            foreach ($request->file('dokumen') as $i => $file) {
                $path = $file->store('dokumen_permohonan', 'public');
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
