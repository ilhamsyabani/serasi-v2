<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\HariLibur;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Import kalender hari libur (tabel `hari_libur`, M-16) dari berkas Excel (.xlsx) atau CSV.
 *
 * Format: kolom A = tanggal, kolom B = keterangan. Baris judul opsional.
 * - Excel: sel tanggal boleh bertipe Date, atau teks seperti di CSV. Hanya sheet pertama yang dibaca.
 * - CSV: pemisah `,` atau `;` (Excel berlokal Indonesia menyimpan CSV dengan `;`).
 * Tanggal teks diterima sebagai YYYY-MM-DD atau DD/MM/YYYY (juga DD-MM-YYYY).
 *
 * Tanggal yang sudah ada dilewati, kecuali $timpa = true — maka keterangannya
 * diperbarui. Seluruh import berjalan dalam satu transaksi.
 */
class HariLiburImportService
{
    public const EKSTENSI = ['xlsx', 'csv'];
    public const UKURAN_MAKS_KB = 2048;
    public const MAKS_BARIS = 1000;

    /**
     * @param  string  $ekstensi  `xlsx` atau `csv` (dari nama berkas asli; path upload sementara tak berekstensi).
     * @return array{ditambah:int, diperbarui:int, dilewati:list<string>, gagal:list<string>}
     */
    public function import(string $path, string $ekstensi, bool $timpa, User $aktor): array
    {
        $hasil = ['ditambah' => 0, 'diperbarui' => 0, 'dilewati' => [], 'gagal' => []];

        try {
            $baris = strtolower($ekstensi) === 'xlsx' ? $this->bacaXlsx($path) : $this->bacaCsv($path);
        } catch (\Throwable) {
            $hasil['gagal'][] = 'Berkas tidak dapat dibaca. Pastikan formatnya Excel (.xlsx) atau CSV yang valid.';
            return $hasil;
        }

        // Baris judul, mis. "Tanggal | Keterangan": baris pertama yang kolom A-nya bukan tanggal.
        $nomorPertama = array_key_first($baris);
        if ($nomorPertama !== null && ! preg_match('/^\d/', $baris[$nomorPertama][0])) {
            unset($baris[$nomorPertama]);
        }

        if (count($baris) > self::MAKS_BARIS) {
            $hasil['gagal'][] = 'Berkas melebihi ' . self::MAKS_BARIS . ' baris data.';
            return $hasil;
        }

        DB::transaction(function () use ($baris, $timpa, $aktor, &$hasil) {
            $sudahDiproses = []; // tanggal ganda dalam berkas yang sama: pakai yang pertama

            foreach ($baris as $noBaris => [$mentahTanggal, $keterangan]) {
                $tanggal = $this->parseTanggal($mentahTanggal);
                if (! $tanggal) {
                    $hasil['gagal'][] = "Baris {$noBaris}: tanggal \"{$mentahTanggal}\" tidak valid.";
                    continue;
                }
                if ($keterangan === '') {
                    $hasil['gagal'][] = "Baris {$noBaris}: keterangan kosong.";
                    continue;
                }
                if (mb_strlen($keterangan) > 150) {
                    $hasil['gagal'][] = "Baris {$noBaris}: keterangan melebihi 150 karakter.";
                    continue;
                }
                if (isset($sudahDiproses[$tanggal])) {
                    $hasil['gagal'][] = "Baris {$noBaris}: tanggal {$tanggal} ganda di dalam berkas.";
                    continue;
                }
                $sudahDiproses[$tanggal] = true;

                $ada = HariLibur::whereDate('tanggal', $tanggal)->first();
                if ($ada) {
                    if ($timpa && $ada->keterangan !== $keterangan) {
                        $ada->update(['keterangan' => $keterangan]);
                        $hasil['diperbarui']++;
                    } else {
                        $hasil['dilewati'][] = $tanggal;
                    }
                    continue;
                }

                HariLibur::create(['tanggal' => $tanggal, 'keterangan' => $keterangan]);
                $hasil['ditambah']++;
            }

            if ($hasil['ditambah'] + $hasil['diperbarui'] > 0) {
                AuditTrail::create([
                    'user_id' => $aktor->id,
                    'user_type' => AuditTrail::USER_TYPE_INTERNAL,
                    'aksi' => 'import_hari_libur',
                    'modul' => 'M-16',
                    'detail' => [
                        'ditambah' => $hasil['ditambah'],
                        'diperbarui' => $hasil['diperbarui'],
                        'dilewati' => count($hasil['dilewati']),
                        'gagal' => count($hasil['gagal']),
                        'timpa' => $timpa,
                    ],
                ]);
            }
        });

        return $hasil;
    }

    /**
     * Baris sheet pertama, dikunci nomor baris Excel (1-based), tiap baris [tanggal, keterangan].
     *
     * @return array<int, array{0:string, 1:string}>
     */
    private function bacaXlsx(string $path): array
    {
        $opsi = new XlsxOptions();
        $opsi->SHOULD_PRESERVE_EMPTY_ROWS = true; // agar nomor baris sesuai yang dilihat pengguna

        $reader = new XlsxReader($opsi);
        $reader->open($path);

        $hasil = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    $sel = $row->toArray();
                    $tanggal = $this->selKeTeks($sel[0] ?? null);
                    $keterangan = trim((string) $this->selKeTeks($sel[1] ?? null));
                    if ($tanggal === '' && $keterangan === '') {
                        continue;
                    }
                    $hasil[$i] = [$tanggal, $keterangan];
                    if (count($hasil) > self::MAKS_BARIS + 1) {
                        break;
                    }
                }
                break; // hanya sheet pertama
            }
        } finally {
            $reader->close();
        }

        return $hasil;
    }

    /**
     * Sel Excel → teks. Sel bertipe Date, atau tanggal yang tersimpan sebagai
     * nomor seri Excel (kolom berformat "General"), dikonversi ke YYYY-MM-DD.
     */
    private function selKeTeks(mixed $nilai): string
    {
        return match (true) {
            $nilai instanceof DateTimeInterface => $nilai->format('Y-m-d'),
            is_int($nilai) || is_float($nilai) => $nilai >= 1 && $nilai < 2958466
                ? Carbon::create(1899, 12, 30)->addDays((int) $nilai)->toDateString()
                : (string) $nilai,
            $nilai === null => '',
            default => trim((string) $nilai),
        };
    }

    /**
     * @return array<int, array{0:string, 1:string}>
     */
    private function bacaCsv(string $path): array
    {
        $isi = (string) file_get_contents($path);
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi); // BOM UTF-8 dari Excel
        $isi = str_replace(["\r\n", "\r"], "\n", $isi);

        $barisMentah = explode("\n", $isi);
        $pertama = collect($barisMentah)->first(fn ($l) => trim($l) !== '') ?? '';
        $pemisah = substr_count($pertama, ';') > substr_count($pertama, ',') ? ';' : ',';

        $hasil = [];
        foreach ($barisMentah as $i => $line) {
            if (trim($line) === '') {
                continue;
            }
            $kolom = str_getcsv($line, $pemisah, '"', '');
            $hasil[$i + 1] = [trim($kolom[0] ?? ''), trim($kolom[1] ?? '')];
        }

        return $hasil;
    }

    private function parseTanggal(string $nilai): ?string
    {
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $nilai, $m)) {
            [$y, $bln, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $nilai, $m)) {
            [$d, $bln, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }

        // checkdate menolak tanggal mustahil seperti 31/02/2026.
        return checkdate($bln, $d, $y) ? Carbon::create($y, $bln, $d)->toDateString() : null;
    }
}
