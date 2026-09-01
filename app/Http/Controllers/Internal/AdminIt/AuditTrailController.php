<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\Permohonan;
use App\Models\User;
use App\Models\Pbf;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditTrail::query()
            ->with(['permohonan:id,no_registrasi,nama_pbf_snapshot'])
            ->latest();

        // Filter: pencarian — OR di 3 tempat berbeda, jadi pakai where dengan OR baru di outer
        if ($search = $request->get('search')) {
            $searchInternal = User::query()
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->where('is_aktif', true)
                ->pluck('id');

            $searchPbf = Pbf::query()
                ->where('nama_pbf', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->pluck('id');

            $searchPermohonan = Permohonan::query()
                ->where('no_registrasi', 'like', "%{$search}%")
                ->orWhere('nama_pbf_snapshot', 'like', "%{$search}%")
                ->pluck('id');

            $query->where(function ($q) use ($search, $searchInternal, $searchPbf, $searchPermohonan) {
                $q->where('aksi', 'like', "%{$search}%")
                  ->orWhere('modul', 'like', "%{$search}%")
                  ->when($searchInternal->isNotEmpty(), function ($oq) use ($searchInternal) {
                      $oq->orWhere(fn($iq) => $iq
                          ->where('user_type', AuditTrail::USER_TYPE_INTERNAL)
                          ->whereIn('user_id', $searchInternal)
                      );
                  })
                  ->when($searchPbf->isNotEmpty(), function ($oq) use ($searchPbf) {
                      $oq->orWhere(fn($iq) => $iq
                          ->where('user_type', AuditTrail::USER_TYPE_PEMOHON)
                          ->whereIn('user_id', $searchPbf)
                      );
                  })
                  ->orWhereIn('permohonan_id', $searchPermohonan);
            });
        }

        // Filter: user_type
        if ($userType = $request->get('user_type')) {
            $query->where('user_type', $userType);
        }

        // Filter: modul
        if ($modul = $request->get('modul')) {
            $query->where('modul', $modul);
        }

        // Filter: permohonan
        if ($permohonanId = $request->get('permohonan_id')) {
            $query->where('permohonan_id', $permohonanId);
        }

        // Filter: rentang tanggal
        if ($dari = $request->get('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $dari);
        }
        if ($sampai = $request->get('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $sampai);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Dropdown filter
        $permohonanList = Permohonan::orderByDesc('tanggal_pengajuan')
            ->get(['id', 'no_registrasi', 'nama_pbf_snapshot']);

        // Modul yang ada di audit_trail
        $modulList = AuditTrail::distinct()->pluck('modul')->filter()->sort()->values();

        // Statistik ringkasan
        $stats = [
            'total' => AuditTrail::count(),
            'internal' => AuditTrail::where('user_type', AuditTrail::USER_TYPE_INTERNAL)->count(),
            'pemohon' => AuditTrail::where('user_type', AuditTrail::USER_TYPE_PEMOHON)->count(),
            'hari_ini' => AuditTrail::whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('internal.adminit.audit-trail.index', compact(
            'logs', 'stats', 'permohonanList', 'modulList'
        ));
    }
}
