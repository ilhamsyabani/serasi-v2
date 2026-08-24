<?php

namespace App\Services;

use App\Models\HariLibur;
use App\Models\Permohonan;
use App\Models\SlaConfig;
use App\Models\StatusLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan SLA per tahap.
 *
 * Aturan yang dijaga di sini (CLAUDE.md §3 poin 3 & §4):
 * - Durasi tahap SELALU diambil dari tabel `sla_config`, tidak pernah di-hardcode.
 * - Hari kerja mengacu tabel `hari_libur`, bukan asumsi Senin–Jumat polos.
 * - Tahap dengan `clock_off = true` (Revisi ke-1/2/3) TIDAK dihitung sebagai
 *   keterlambatan staff; tahap tersebut mengembalikan state `clock_off`.
 *
 * Daftar hari libur & konfigurasi SLA di-memoize per instance, karena kalkulasi ini
 * dipanggil berulang untuk setiap baris permohonan di dashboard.
 */
class SlaCalculator
{
    public const STATE_ON_TIME = 'on_time';
    public const STATE_AT_RISK = 'at_risk';
    public const STATE_LATE = 'late';
    public const STATE_CLOCK_OFF = 'clock_off';
    public const STATE_SELESAI = 'selesai';
    public const STATE_TANPA_SLA = 'tanpa_sla';
    public const STATE_SELESAI_LEBIH_AWAL = 'selesai_lebih_awal';

    /** Sisa hari kerja <= nilai ini dianggap "at risk" (kuning). */
    private const AMBANG_AT_RISK = 1;

    private ?array $hariLibur = null;
    private ?Collection $konfigurasi = null;

    /** Tanggal libur nasional sebagai map Y-m-d => true, untuk lookup O(1). */
    private function hariLibur(): array
    {
        return $this->hariLibur ??= HariLibur::pluck('tanggal')
            ->map(fn ($t) => Carbon::parse($t)->format('Y-m-d'))
            ->flip()
            ->all();
    }

    /** Konfigurasi SLA aktif, dikunci berdasarkan `kode_tahap`. */
    public function konfigurasi(): Collection
    {
        return $this->konfigurasi ??= SlaConfig::where('is_active', true)->get()->keyBy('kode_tahap');
    }

    private function isHariKerja(CarbonInterface $tanggal): bool
    {
        return $tanggal->isoWeekday() <= 5 && ! isset($this->hariLibur()[$tanggal->format('Y-m-d')]);
    }

    /**
     * Jumlah hari kerja yang terlewati antara dua waktu.
     * Menerima Carbon maupun string agar kompatibel dengan pemanggil lama.
     */
    public function hitungHariKerja(CarbonInterface|string $tanggalMulai, CarbonInterface|string|null $tanggalSelesai = null): int
    {
        $current = Carbon::parse($tanggalMulai)->copy();
        $selesai = Carbon::parse($tanggalSelesai ?? now());

        // Hitung hari kerja pada rentang (mulai, selesai]. Maju satu hari dulu,
        // lalu hentikan bila sudah melewati `selesai` SEBELUM menghitungnya —
        // tanpa cek ini, mulai≈selesai keliru menghasilkan 1 (bukan 0).
        $hariKerja = 0;
        while (true) {
            $current->addDay();
            if ($current > $selesai) {
                break;
            }
            if ($this->isHariKerja($current)) {
                $hariKerja++;
            }
        }

        return $hariKerja;
    }

    /** Tanggal setelah ditambah sejumlah hari kerja (melompati akhir pekan & hari libur). */
    public function tambahHariKerja(CarbonInterface|string $mulai, int $jumlahHari): Carbon
    {
        $tanggal = Carbon::parse($mulai)->copy();

        for ($i = 0; $i < $jumlahHari; $i++) {
            do {
                $tanggal->addDay();
            } while (! $this->isHariKerja($tanggal));
        }

        return $tanggal;
    }

    /**
     * Evaluasi SLA untuk satu baris `status_log`.
     *
     * @return array{state:string,label:string,durasi_sla:?int,terpakai:int,sisa:?int,deadline:?Carbon,clock_off:bool}
     */
    public function evaluasiLog(StatusLog $log): array
    {
        $config = $this->konfigurasi()->get($log->status);
        $selesai = $log->waktu_selesai !== null;
        $terpakai = $log->durasi_hari_kerja ?? $this->hitungHariKerja($log->waktu_mulai, $log->waktu_selesai);

        $dasar = [
            'durasi_sla' => $config?->durasi,
            'terpakai' => $terpakai,
            'sisa' => null,
            'deadline' => null,
            'clock_off' => (bool) $log->is_clock_off,
        ];

        // Tahap revisi: menunggu pemohon, jam SLA staff berhenti.
        if ($log->is_clock_off || $config?->clock_off) {
            return $dasar + ['state' => self::STATE_CLOCK_OFF, 'label' => 'Clock-off'];
        }

        if ($config === null || $config->durasi === null) {
            if ($selesai) {
                return $dasar + ['state' => self::STATE_SELESAI, 'label' => 'Selesai'];
            }
            return $dasar + ['state' => self::STATE_TANPA_SLA, 'label' => 'Tanpa SLA'];
        }

        $deadline = $config->satuan === SlaConfig::SATUAN_HARI_KALENDER
            ? Carbon::parse($log->waktu_mulai)->copy()->addDays($config->durasi)
            : $this->tambahHariKerja($log->waktu_mulai, $config->durasi);

        $sisa = $config->durasi - $terpakai;
        $dasar['sisa'] = $sisa;
        $dasar['deadline'] = $deadline;

        if ($selesai) {
            if ($sisa > 0) {
                return $dasar + [
                    'state' => self::STATE_SELESAI_LEBIH_AWAL,
                    'label' => 'Selesai ' . abs($sisa) . ' hari lebih awal',
                ];
            }
            return $dasar + [
                'state' => self::STATE_SELESAI,
                'label' => 'Tepat waktu',
            ];
        }

        if ($sisa < 0) {
            return $dasar + ['state' => self::STATE_LATE, 'label' => 'Telat ' . abs($sisa) . ' hari'];
        }

        if ($sisa <= self::AMBANG_AT_RISK) {
            return $dasar + ['state' => self::STATE_AT_RISK, 'label' => 'Sisa ' . $sisa . ' hari'];
        }

        return $dasar + ['state' => self::STATE_ON_TIME, 'label' => 'Sisa ' . $sisa . ' hari'];
    }

    /** Evaluasi SLA permohonan — tahap berjalan, atau tahap akhir jika sudah selesai. */
    public function evaluasiPermohonan(Permohonan $permohonan): ?array
    {
        $berjalan = $permohonan->statusLog->firstWhere('waktu_selesai', null);
        if ($berjalan) {
            return $this->evaluasiLog($berjalan);
        }

        // Permohonan selesai: ambil tahap terakhir yang sudah memiliki waktu_selesai.
        $terakhir = $permohonan->statusLog->sortBy(fn ($log) => $log->waktu_mulai)->last();
        return $terakhir ? $this->evaluasiLog($terakhir) : null;
    }

    /**
     * Ringkasan jumlah permohonan per state SLA — dipakai kartu statistik dashboard.
     *
     * @param  Collection<int,Permohonan>  $permohonans
     * @return array{on_time:int,at_risk:int,late:int,clock_off:int,selesai:int,selesai_lebih_awal:int,tanpa_sla:int}
     */
    public function ringkasan(Collection $permohonans): array
    {
        $ringkasan = ['on_time' => 0, 'at_risk' => 0, 'late' => 0, 'clock_off' => 0, 'selesai' => 0, 'selesai_lebih_awal' => 0, 'tanpa_sla' => 0];

        foreach ($permohonans as $permohonan) {
            $state = $this->evaluasiPermohonan($permohonan)['state'] ?? null;
            if ($state !== null && array_key_exists($state, $ringkasan)) {
                $ringkasan[$state]++;
            }
        }

        return $ringkasan;
    }
}
