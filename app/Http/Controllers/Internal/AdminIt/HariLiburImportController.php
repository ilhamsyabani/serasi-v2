<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Services\HariLiburImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Import massal hari libur dari Excel/CSV (M-16, Admin IT).
 * Form-nya berupa modal di halaman daftar Hari Libur.
 */
class HariLiburImportController extends Controller
{
    public function store(Request $request, HariLiburImportService $service)
    {
        Gate::authorize('create', HariLibur::class);

        $request->validate([
            'berkas' => 'required|file|extensions:' . implode(',', HariLiburImportService::EKSTENSI)
                . '|max:' . HariLiburImportService::UKURAN_MAKS_KB,
            'timpa' => 'nullable|boolean',
        ], [
            'berkas.required' => 'Pilih berkas Excel atau CSV terlebih dahulu.',
            'berkas.extensions' => 'Berkas harus berformat Excel (.xlsx) atau CSV.',
            'berkas.max' => 'Ukuran berkas maksimal ' . (HariLiburImportService::UKURAN_MAKS_KB / 1024) . ' MB.',
        ]);

        $berkas = $request->file('berkas');
        $hasil = $service->import(
            $berkas->getRealPath(),
            $berkas->getClientOriginalExtension(),
            $request->boolean('timpa'),
            $request->user(),
        );

        return redirect()->route('internal.adminit.hari-libur.index')->with('hasilImport', $hasil);
    }

    /** Contoh berkas Excel yang siap diisi. */
    public function template()
    {
        Gate::authorize('create', HariLibur::class);

        $tahun = now()->year + 1;
        $baris = [
            ['Tanggal', 'Keterangan'],
            ["{$tahun}-01-01", 'Tahun Baru Masehi'],
            ["{$tahun}-08-17", 'Hari Kemerdekaan RI'],
            ["{$tahun}-12-25", 'Hari Raya Natal'],
        ];

        return response()->streamDownload(function () use ($baris) {
            $writer = new XlsxWriter();
            $writer->openToFile('php://output');
            foreach ($baris as $b) {
                $writer->addRow(Row::fromValues($b));
            }
            $writer->close();
        }, 'template-hari-libur.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
