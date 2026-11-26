<?php

namespace App\Services;

use App\Models\Permohonan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Laporan & Ekspor (M-14) untuk Ketua Tim dan Staff Sertifikasi.
 *
 * Cakupan data mengikuti dashboard masing-masing:
 * - Ketua Tim: permohonan yang didisposisikan ke timnya.
 * - Staff: permohonan yang sedang ia evaluasi (distribusi aktif).
 */
class LaporanService
{
    public const HEADER = [
        'No', 'No. Registrasi', 'Nama PBF', 'NIB', 'Tanggal Pengajuan', 'Jenis Pengajuan',
        'Status', 'Revisi Ke', 'Ketua Tim', 'Evaluator', 'Evaluasi Terakhir', 'Hasil Evaluasi Terakhir',
        'No. Surat', 'Tanggal Terbit', 'SLA Tahap Berjalan',
    ];

    public function __construct(private SlaCalculator $sla) {}

    /** Query permohonan sesuai cakupan role + filter (search, status, tanggal_dari, tanggal_sampai). */
    public function query(User $user, array $filter): Builder
    {
        $query = Permohonan::query();

        if ($user->isKetuaTim()) {
            $query->whereHas('disposisi', fn ($q) => $q->where('ketua_tim_id', $user->id));
        } elseif ($user->isStaffSertifikasi()) {
            $query->whereHas('distribusiAktif', fn ($q) => $q->where('staff_id', $user->id));
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->filterTabel($filter)
            ->with(['statusLog', 'disposisi.ketuaTim', 'distribusiAktif.staff', 'evaluasiTerakhir', 'suratPengesahan', 'pengajuanAsal'])
            ->orderByDesc('tanggal_pengajuan')
            ->orderByDesc('id');
    }

    /** Satu baris laporan per permohonan, urut sesuai HEADER. */
    public function baris(Collection $permohonan): array
    {
        $status = Permohonan::statusList();

        return $permohonan->values()->map(function (Permohonan $p, int $i) use ($status) {
            $eval = $p->evaluasiTerakhir;
            $surat = $p->suratPengesahan;

            return [
                $i + 1,
                $p->no_registrasi,
                $p->nama_pbf_snapshot,
                (string) $p->nib_snapshot,
                $p->tanggal_pengajuan?->format('d/m/Y') ?? '',
                $p->isPengajuanUlang() ? 'Pengajuan Ulang (dari ' . ($p->pengajuanAsal?->no_registrasi ?? '-') . ')' : 'Baru',
                $status[$p->status_saat_ini] ?? $p->status_saat_ini,
                (int) $p->revisi_ke . ' / ' . Permohonan::MAKS_REVISI,
                $p->disposisi?->ketuaTim?->nama ?? '',
                $p->distribusiAktif?->staff?->nama ?? '',
                $eval?->tanggal_evaluasi?->format('d/m/Y') ?? '',
                $eval ? ($eval->hasil === 'lengkap' ? 'Lengkap' : 'Tidak Lengkap') : '',
                $surat?->nomor_surat ?? '',
                $surat?->tanggal_upload?->format('d/m/Y') ?? '',
                $this->sla->evaluasiPermohonan($p)['label'] ?? '',
            ];
        })->all();
    }

    /** Jumlah per status + total, untuk kartu ringkasan & sheet "Ringkasan". */
    public function ringkasan(Collection $permohonan): array
    {
        $hitung = $permohonan->countBy('status_saat_ini');

        return collect(Permohonan::statusList())
            ->map(fn ($label, $kode) => ['label' => $label, 'jumlah' => (int) ($hitung[$kode] ?? 0)])
            ->all();
    }

    /** Tulis XLSX (sheet Data + Ringkasan) ke $tujuan, mis. 'php://output'. */
    public function tulisXlsx(string $tujuan, User $user, array $filter, Collection $permohonan): void
    {
        $tebal = (new Style())->setFontBold();
        $writer = new XlsxWriter();
        $writer->openToFile($tujuan);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Data Permohonan');
        foreach ([5, 22, 34, 18, 16, 30, 26, 10, 22, 22, 16, 20, 26, 14, 22] as $kolom => $lebar) {
            $sheet->setColumnWidth($lebar, $kolom + 1);
        }

        $writer->addRow(Row::fromValues(['Laporan Pengajuan Rancangan Denah PBF'], $tebal));
        foreach ($this->keteranganFilter($user, $filter) as $ket) {
            $writer->addRow(Row::fromValues($ket));
        }
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(self::HEADER, $tebal));
        foreach ($this->baris($permohonan) as $b) {
            $writer->addRow(Row::fromValues($b));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Ringkasan');
        $writer->getCurrentSheet()->setColumnWidth(30, 1);
        $writer->addRow(Row::fromValues(['Status', 'Jumlah'], $tebal));
        foreach ($this->ringkasan($permohonan) as $r) {
            $writer->addRow(Row::fromValues([$r['label'], $r['jumlah']]));
        }
        $writer->addRow(Row::fromValues(['Total', $permohonan->count()], $tebal));

        $writer->close();
    }

    /** PDF A4 landscape: tabel data + ringkasan per status. */
    public function pdf(User $user, array $filter, Collection $permohonan): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('internal.laporan.pdf', [
            'user' => $user,
            // Tanpa "Dicetak oleh"/"Tanggal cetak" — sudah ada di footer setiap halaman.
            'keterangan' => array_slice($this->keteranganFilter($user, $filter), 2),
            'dicetak' => now()->format('d/m/Y H:i'),
            'baris' => $this->baris($permohonan),
            // Hanya status yang ada isinya, agar tabel ringkasan ringkas & tidak terbelah halaman.
            'ringkasan' => array_filter($this->ringkasan($permohonan), fn ($r) => $r['jumlah'] > 0),
            // Kolom evaluator hanya berguna bagi Ketua Tim; bagi Staff isinya selalu dirinya.
            'tampilEvaluator' => $user->isKetuaTim(),
        ])->setPaper('a4', 'landscape');

        // dompdf tidak mendukung counter(pages) di CSS; total halaman ditulis lewat canvas.
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 100, $canvas->get_height() - 28, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 7, [0.58, 0.64, 0.72]);

        return $pdf;
    }

    public function namaFile(User $user, string $ekstensi = 'xlsx'): string
    {
        return 'laporan-permohonan-' . ($user->isKetuaTim() ? 'ketua-tim' : 'staff') . '-' . now()->format('Ymd-His') . '.' . $ekstensi;
    }

    private function keteranganFilter(User $user, array $filter): array
    {
        $periode = ($filter['tanggal_dari'] ?? null) || ($filter['tanggal_sampai'] ?? null)
            ? ($filter['tanggal_dari'] ?? '…') . ' s.d. ' . ($filter['tanggal_sampai'] ?? '…')
            : 'Semua';

        return [
            ['Dicetak oleh', $user->nama . ' (' . ($user->isKetuaTim() ? 'Ketua Tim' : 'Staff Sertifikasi') . ')'],
            ['Tanggal cetak', now()->format('d/m/Y H:i')],
            ['Periode pengajuan', $periode],
            ['Status', isset($filter['status']) && $filter['status'] !== '' ? (Permohonan::statusList()[$filter['status']] ?? $filter['status']) : 'Semua'],
            ['Pencarian', ($filter['search'] ?? '') !== '' ? $filter['search'] : '-'],
        ];
    }
}
