<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman daftar notifikasi untuk user internal BBPOM (Staff, Ketua Tim, Kepala Balai).
 */
class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Notifikasi::query()
            ->where('tujuan_tipe', '!=', Notifikasi::TUJUAN_PEMOHON)
            ->where('tujuan_id', $user->id)
            ->with('permohonan')
            ->latest();

        if ($request->boolean('belum_dibaca')) {
            $query->where('status_kirim', '!=', Notifikasi::STATUS_TERKIRIM);
        }

        if ($request->get('channel')) {
            $query->where('channel', $request->get('channel'));
        }

        $notifikasis = $query->paginate(20)->withQueryString();

        return view('internal.notifikasi.index', compact('notifikasis'));
    }
}
