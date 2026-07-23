<?php

namespace App\Http\Controllers\Internal\Permohonan;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use Illuminate\Support\Facades\Gate;

class PermohonanController extends Controller
{
    /**
     * Detail permohonan untuk user internal. Pemohon punya rute sendiri
     * (`pemohon.permohonan.show`) dengan guard `pemohon`, jadi tidak perlu
     * dicabangkan di sini — rute ini sudah dilindungi middleware `auth` + `role`.
     */
    public function show(Permohonan $permohonan)
    {
        Gate::authorize('view', $permohonan);

        $permohonan->load([
            'statusLog',
            'dokumen',
            'disposisi.ketuaTim',
            'distribusiAktif.staff',
            'evaluasi',
        ]);

        return view('internal.permohonan.show', compact('permohonan'));
    }
}
