<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman daftar notifikasi untuk pemohon (PBF).
 */
class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $pbf = Auth::guard('pemohon')->user();

        $query = Notifikasi::query()
            ->where('tujuan_tipe', Notifikasi::TUJUAN_PEMOHON)
            ->where('tujuan_id', $pbf->id)
            ->with('permohonan')
            ->latest();

        if ($request->get('channel')) {
            $query->where('channel', $request->get('channel'));
        }

        $notifikasis = $query->paginate(20)->withQueryString();

        return view('pemohon.notifikasi.index', compact('notifikasis'));
    }
}
