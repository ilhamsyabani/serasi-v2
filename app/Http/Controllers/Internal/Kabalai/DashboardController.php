<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\SlaCalculator;

/**
 * Dashboard Kepala Balai — pengawasan (oversight) view-only atas SELURUH
 * permohonan balai, termasuk pengajuan ulang mandiri pemohon (kepala_balai_id NULL).
 * Kepala Balai tidak punya tombol aksi apa pun di sini (CLAUDE.md §3 poin 8);
 * satu-satunya aksi miliknya (input permohonan baru) ada di menu Permohonan.
 */
class DashboardController extends Controller
{
    public function index(SlaCalculator $sla)
    {
        // Timeline & badge SLA tiap baris membaca statusLog + aktor; eager load hindari N+1.
        $permohonans = Permohonan::query()
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff'])
            ->latest()
            ->get();

        return view('internal.kabalai.dashboard', [
            'permohonans' => $permohonans,
            'slaRingkasan' => $sla->ringkasan($permohonans),
        ]);
    }
}
