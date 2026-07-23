<?php

namespace App\Http\Controllers\Internal\Staff;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Models\SuratPengesahan;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SuratPengesahanController extends Controller
{
    public function edit(Permohonan $permohonan)
    {
        abort_if($permohonan->status_saat_ini !== Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, 403);

        return view('internal.staff.surat.edit', compact('permohonan'));
    }

    public function update(Request $request, Permohonan $permohonan)
    {
        $request->validate([
            'file_surat' => 'required|file|mimes:pdf|max:10240',
            'nomor_surat' => 'required|string|max:100',
        ]);

        $path = $request->file('file_surat')->store('surat_pengesahan', 'public');

        SuratPengesahan::create([
            'permohonan_id' => $permohonan->id,
            'staff_id' => Auth::id(),
            'path_file' => $path,
            'nomor_surat' => $request->nomor_surat,
            'tanggal_upload' => now(),
        ]);

        app(StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN, 'Surat pengesahan diterbitkan', Auth::user(), 'internal');

        return redirect()->route('internal.staff.dashboard')->with('success', 'Surat pengesahan berhasil diterbitkan.');
    }
}
