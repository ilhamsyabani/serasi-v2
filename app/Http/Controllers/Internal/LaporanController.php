<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Laporan & Ekspor (M-14) — Ketua Tim (timnya) dan Staff (miliknya). */
class LaporanController extends Controller
{
    public function index(Request $request, LaporanService $laporan)
    {
        $user = Auth::user();
        $filter = $this->filter($request);

        $semua = $laporan->query($user, $filter)->get();
        $halaman = $laporan->query($user, $filter)->paginate(15)->withQueryString();

        return view('internal.laporan.index', [
            'filter' => $filter,
            'permohonan' => $halaman,
            'baris' => $laporan->baris($halaman->getCollection()),
            'nomorAwal' => $halaman->firstItem() ?? 1,
            'ringkasan' => $laporan->ringkasan($semua),
            'total' => $semua->count(),
            'statusList' => Permohonan::statusList(),
        ]);
    }

    public function export(Request $request, LaporanService $laporan)
    {
        $user = Auth::user();
        $filter = $this->filter($request);
        $data = $laporan->query($user, $filter)->get();

        return response()->streamDownload(
            fn () => $laporan->tulisXlsx('php://output', $user, $filter, $data),
            $laporan->namaFile($user),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function exportPdf(Request $request, LaporanService $laporan)
    {
        $user = Auth::user();
        $filter = $this->filter($request);

        return $laporan->pdf($user, $filter, $laporan->query($user, $filter)->get())
            ->download($laporan->namaFile($user, 'pdf'));
    }

    private function filter(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(array_keys(Permohonan::statusList()))],
            'tanggal_dari' => 'nullable|date',
            'tanggal_sampai' => 'nullable|date|after_or_equal:tanggal_dari',
        ]);
    }
}
