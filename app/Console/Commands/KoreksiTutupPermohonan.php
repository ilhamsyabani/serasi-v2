<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use App\Models\Evaluasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Models\SuratPengesahan;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\StatusTransitionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi permohonan yang keliru dinilai "Lengkap" padahal kuota revisi sudah habis:
 * dipindahkan ke `ditutup_pengajuan_ulang` lewat StatusTransitionService.
 *
 * Riwayat lama TIDAK dihapus/ditimpa (audit trail instansi). Koreksi ditambahkan
 * sebagai baris baru: evaluasi koreksi, status_log baru, dan audit_trail.
 *
 * Bila `status_saat_ini` sempat diubah langsung di DB tanpa status_log, nilainya
 * lebih dulu disinkronkan ke status_log yang masih terbuka (tercatat di audit_trail).
 *
 * --dry-run menjalankan seluruh langkah di dalam transaksi lalu di-ROLLBACK,
 * dan TIDAK mengirim WA.
 */
class KoreksiTutupPermohonan extends Command
{
    protected $signature = 'permohonan:koreksi-tutup
        {no_registrasi : No. registrasi permohonan, mis. PBF/DENAH/2026/00014}
        {--alasan= : Alasan koreksi (wajib), dicatat di evaluasi & audit_trail}
        {--aktor= : ID user Admin IT pelaku koreksi (wajib)}
        {--dry-run : Simulasi — semua perubahan di-rollback, WA tidak dikirim}
        {--tanpa-wa : Simpan koreksi tanpa mengirim WA (mis. WA sudah dikirim dari lingkungan lain)}';

    protected $description = 'Koreksi permohonan yang keliru "Lengkap" setelah kuota revisi habis menjadi ditutup_pengajuan_ulang.';

    /** Status asal yang boleh dikoreksi (belum terbit). */
    private const STATUS_BOLEH = [
        Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
        Permohonan::STATUS_PROSES_EVALUASI,
    ];

    public function handle(StatusTransitionService $transisi, NotifikasiService $notif): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $alasan = trim((string) $this->option('alasan'));

        if ($alasan === '') {
            $this->error('--alasan wajib diisi.');
            return self::FAILURE;
        }

        $aktor = User::find($this->option('aktor'));
        if (!$aktor || !$aktor->isAdminIt()) {
            $this->error('--aktor harus ID user Admin IT yang valid.');
            return self::FAILURE;
        }

        $p = Permohonan::where('no_registrasi', $this->argument('no_registrasi'))->first();
        if (!$p) {
            $this->error('Permohonan tidak ditemukan.');
            return self::FAILURE;
        }

        $logAktif = StatusLog::where('permohonan_id', $p->id)->whereNull('waktu_selesai')->orderByDesc('id')->first();
        $jumlahRevisi = StatusLog::where('permohonan_id', $p->id)->whereIn('status', Permohonan::STATUS_REVISI)->count();
        $evaluasiTerakhir = Evaluasi::where('permohonan_id', $p->id)->orderByDesc('id')->first();

        $this->line($dryRun ? '<comment>=== DRY RUN — tidak ada yang disimpan, WA tidak dikirim ===</comment>' : '<info>=== EKSEKUSI ===</info>');
        $this->newLine();
        $this->table(['Item', 'Nilai'], [
            ['Permohonan', "#{$p->id} {$p->no_registrasi}"],
            ['PBF', $p->nama_pbf_snapshot],
            ['status_saat_ini', $p->status_saat_ini],
            ['status_log terbuka', $logAktif ? "#{$logAktif->id} {$logAktif->status}" : '-'],
            ['Jumlah siklus revisi', "$jumlahRevisi / " . Permohonan::MAKS_REVISI],
            ['Evaluasi terakhir', $evaluasiTerakhir ? "#{$evaluasiTerakhir->id} siklus {$evaluasiTerakhir->siklus_ke} = {$evaluasiTerakhir->hasil}" : '-'],
            ['Pelaku koreksi', "#{$aktor->id} {$aktor->nama}"],
        ]);

        // ---- Penjaga ----
        $gagal = [];
        if (SuratPengesahan::where('permohonan_id', $p->id)->exists()) {
            $gagal[] = 'Surat pengesahan sudah diunggah — tidak boleh dikoreksi lewat perintah ini.';
        }
        if (Permohonan::where('parent_permohonan_id', $p->id)->exists()) {
            $gagal[] = 'Sudah ada pengajuan ulang yang tertaut ke permohonan ini.';
        }
        if (!$logAktif) {
            $gagal[] = 'Tidak ada status_log yang masih terbuka.';
        } elseif (!in_array($logAktif->status, self::STATUS_BOLEH, true)) {
            $gagal[] = "status_log terbuka berstatus {$logAktif->status}, bukan " . implode('/', self::STATUS_BOLEH) . '.';
        }
        if ($jumlahRevisi < Permohonan::MAKS_REVISI) {
            $gagal[] = "Baru $jumlahRevisi siklus revisi — kuota belum habis, seharusnya revisi berikutnya, bukan ditutup.";
        }
        if ($gagal) {
            foreach ($gagal as $g) {
                $this->error($g);
            }
            return self::FAILURE;
        }

        $perluSinkron = $p->status_saat_ini !== $logAktif->status;

        DB::beginTransaction();
        try {
            // 1. Sinkronkan status_saat_ini yang diedit manual ke status_log terbuka.
            if ($perluSinkron) {
                $this->line("1. Sinkron status_saat_ini: {$p->status_saat_ini} -> {$logAktif->status} (edit manual DB tanpa status_log)");
                AuditTrail::create([
                    'user_id' => $aktor->id,
                    'user_type' => 'internal',
                    'aksi' => 'koreksi_data',
                    'modul' => 'M-07',
                    'permohonan_id' => $p->id,
                    'detail' => [
                        'kolom' => 'status_saat_ini',
                        'dari' => $p->status_saat_ini,
                        'ke' => $logAktif->status,
                        'catatan' => 'Membatalkan edit manual langsung di database yang tidak tercatat di status_log; disinkronkan sebelum koreksi resmi.',
                    ],
                ]);
                $p->status_saat_ini = $logAktif->status;
                $p->saveQuietly();
            } else {
                $this->line('1. status_saat_ini sudah sinkron dengan status_log — dilewati.');
            }

            // 2. Evaluasi koreksi (baris baru; evaluasi keliru tetap tersimpan).
            $catatanEvaluasi = '[KOREKSI'
                . ($evaluasiTerakhir ? " atas evaluasi #{$evaluasiTerakhir->id} ({$evaluasiTerakhir->hasil})" : '')
                . "] $alasan";
            $evaluasi = Evaluasi::create([
                'permohonan_id' => $p->id,
                'staff_id' => $aktor->id,
                'siklus_ke' => $evaluasiTerakhir?->siklus_ke ?? 0,
                'hasil' => Evaluasi::HASIL_TIDAK_LENGKAP,
                'catatan' => $catatanEvaluasi,
                'tanggal_evaluasi' => now(),
            ]);
            $this->line("2. Evaluasi koreksi #{$evaluasi->id}: siklus {$evaluasi->siklus_ke} = tidak_lengkap");

            // 3. Transisi resmi — status_log baru + audit_trail.
            $log = $transisi->transisi(
                $p,
                Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG,
                "Koreksi: $alasan",
                $aktor,
                'internal'
            );
            $this->line("3. Transisi: {$logAktif->status} -> {$log->status} (status_log #{$log->id}; log #{$logAktif->id} ditutup)");

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan dibatalkan: ' . $e->getMessage());
            return self::FAILURE;
        }

        // 4. Notifikasi WA (setelah commit): pemohon, staff evaluator aktif, ketua tim.
        if (!$dryRun && $this->option('tanpa-wa')) {
            $this->line('4. --tanpa-wa: WA tidak dikirim.');
            $this->info('Koreksi selesai.');
            return self::SUCCESS;
        }

        $p->refresh();
        $statusAsal = $logAktif->status;
        $penerima = [
            ['Pemohon', Notifikasi::TUJUAN_PEMOHON, $p->pbf_id, $p->pbf?->no_whatsapp, $this->pesanPemohon($p)],
        ];
        if ($staff = User::find($p->distribusiAktif?->staff_id)) {
            $penerima[] = ["Staff ({$staff->nama})", Notifikasi::TUJUAN_STAFF, $staff->id, $staff->no_whatsapp, $this->pesanInternal($p, $statusAsal, $alasan)];
        } else {
            $this->warn('   Staff aktif tidak ditemukan — WA staff dilewati.');
        }
        if ($katim = User::find($p->disposisi?->ketua_tim_id)) {
            $penerima[] = ["Ketua Tim ({$katim->nama})", Notifikasi::TUJUAN_KETUA_TIM, $katim->id, $katim->no_whatsapp, $this->pesanInternal($p, $statusAsal, $alasan)];
        } else {
            $this->warn('   Ketua Tim tidak ditemukan — WA ketua tim dilewati.');
        }

        foreach ($penerima as [$label, $tujuanTipe, $tujuanId, $noWa, $pesan]) {
            if ($dryRun) {
                $this->line("4. [dry-run] WA ke {$label} (" . ($noWa ?: 'NOMOR KOSONG') . ') TIDAK dikirim. Pratinjau:');
                $this->line('   | ' . str_replace("\n", "\n   | ", $pesan));
                $this->newLine();
                continue;
            }
            $hasil = $notif->kirim($p, $tujuanTipe, $tujuanId, Notifikasi::CHANNEL_WHATSAPP, 'DITUTUP_PENGAJUAN_ULANG', $pesan);
            $this->line("4. WA ke {$label} ({$noWa}): {$hasil->status_kirim}" . ($hasil->error_message ? " — {$hasil->error_message}" : ''));
        }

        if ($dryRun) {
            $this->comment('Dry run selesai — semua perubahan di-rollback.');
        } else {
            $this->info('Koreksi selesai.');
        }
        return self::SUCCESS;
    }

    private function pesanPemohon(Permohonan $p): string
    {
        return "⚠️ *Permohonan Ditutup*\n\n"
            . "No. Reg: {$p->no_registrasi}\n"
            . "PBF: {$p->nama_pbf_snapshot}\n\n"
            . 'Setelah ' . Permohonan::MAKS_REVISI . " kali revisi, permohonan tidak memenuhi persyaratan.\n"
            . 'Silakan diskusi mandiri terlebih dahulu ke tim Balai Besar POM di Surabaya melalui email '
            . 'sertifikasisby@gmail.com dan whatsapp layanan 087711500533';
    }

    private function pesanInternal(Permohonan $p, string $statusAsal, string $alasan): string
    {
        return "ℹ️ *Koreksi Status Permohonan*\n\n"
            . "No. Reg: {$p->no_registrasi}\n"
            . "PBF: {$p->nama_pbf_snapshot}\n\n"
            . 'Status dikoreksi oleh Admin IT dari *' . str_replace('_', ' ', $statusAsal) . "* "
            . "menjadi *ditutup pengajuan ulang*.\n"
            . "Alasan: {$alasan}\n\n"
            . 'Surat pengesahan untuk permohonan ini TIDAK perlu diterbitkan.';
    }
}
