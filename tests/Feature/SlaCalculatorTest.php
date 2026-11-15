<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Permohonan;
use App\Models\SlaConfig;
use App\Models\StatusLog;
use App\Services\SlaCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function sla(): SlaCalculator
    {
        return new SlaCalculator();
    }

    public function test_hari_kerja_melewati_akhir_pekan(): void
    {
        // Jumat 2026-01-02 -> Senin 2026-01-05 = 1 hari kerja (Sabtu & Minggu dilewati).
        $hari = $this->sla()->hitungHariKerja('2026-01-02 09:00:00', '2026-01-05 09:00:00');

        $this->assertSame(1, $hari);
    }

    public function test_hari_libur_nasional_tidak_dihitung(): void
    {
        HariLibur::create(['tanggal' => '2026-01-05', 'keterangan' => 'Libur uji']);

        // Jumat -> Selasa biasanya 2 hari kerja (Sen, Sel); Senin libur -> tinggal 1.
        $hari = $this->sla()->hitungHariKerja('2026-01-02 09:00:00', '2026-01-06 09:00:00');

        $this->assertSame(1, $hari);
    }

    public function test_hari_kerja_dihitung_per_tanggal_bukan_per_24_jam(): void
    {
        // Masuk Senin 2026-01-05 sore. Di tanggal yang sama -> 0, jam berapa pun.
        $this->assertSame(0, $this->sla()->hitungHariKerja('2026-01-05 14:00:00', '2026-01-05 23:59:59'));

        // Selasa 00:00 sudah terhitung 1 hari kerja (H+1), walau belum 24 jam.
        $this->assertSame(1, $this->sla()->hitungHariKerja('2026-01-05 14:00:00', '2026-01-06 00:00:00'));
        $this->assertSame(1, $this->sla()->hitungHariKerja('2026-01-05 14:00:00', '2026-01-06 23:59:59'));

        // Rabu 00:00 -> 2 hari kerja.
        $this->assertSame(2, $this->sla()->hitungHariKerja('2026-01-05 14:00:00', '2026-01-07 00:00:00'));
    }

    public function test_deadline_jatuh_di_akhir_hari_kerja_ke_n(): void
    {
        // Senin 14:00 + 1 HK -> Selasa 23:59:59 (bukan Selasa 14:00).
        $this->assertSame('2026-01-06 23:59:59', $this->sla()->tambahHariKerja('2026-01-05 14:00:00', 1)->format('Y-m-d H:i:s'));

        // Masuk Sabtu: hari kerja ke-1 = Senin, batasnya Senin 23:59:59.
        $this->assertSame('2026-01-05 23:59:59', $this->sla()->tambahHariKerja('2026-01-03 10:00:00', 1)->format('Y-m-d H:i:s'));

        // Jumat + 3 HK melompati akhir pekan -> Rabu 23:59:59.
        $this->assertSame('2026-01-07 23:59:59', $this->sla()->tambahHariKerja('2026-01-02 19:03:00', 3)->format('Y-m-d H:i:s'));
    }

    public function test_status_telat_tepat_setelah_deadline_terlewati(): void
    {
        SlaConfig::create(['kode_tahap' => 'pengajuan', 'nama_tahap' => 'Pengajuan', 'durasi' => 1, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);

        $permohonan = $this->permohonan('pengajuan');
        $log = StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'pengajuan',
            'waktu_mulai' => Carbon::parse('2026-01-05 14:00:00'), // Senin
            'is_clock_off' => false,
        ]);

        try {
            // Selasa 23:59 — masih dalam batas (hari terakhir).
            Carbon::setTestNow('2026-01-06 23:59:00');
            $hasil = $this->sla()->evaluasiLog($log);
            $this->assertSame(SlaCalculator::STATE_AT_RISK, $hasil['state']);
            $this->assertSame(0, $hasil['sisa']);
            $this->assertTrue(now()->lte($hasil['deadline']));

            // Rabu 00:00 — deadline lewat, langsung telat.
            Carbon::setTestNow('2026-01-07 00:00:00');
            $hasil = $this->sla()->evaluasiLog($log);
            $this->assertSame(SlaCalculator::STATE_LATE, $hasil['state']);
            $this->assertSame(-1, $hasil['sisa']);
            $this->assertTrue(now()->gt($hasil['deadline']));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_tahap_selesai_terlambat_tidak_berlabel_tepat_waktu(): void
    {
        SlaConfig::create(['kode_tahap' => 'pengajuan', 'nama_tahap' => 'Pengajuan', 'durasi' => 1, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);
        $permohonan = $this->permohonan('pengajuan');

        $buat = fn (string $selesai) => StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'pengajuan',
            'waktu_mulai' => Carbon::parse('2026-01-05 14:00:00'), // Senin
            'waktu_selesai' => Carbon::parse($selesai),
            'is_clock_off' => false,
        ]);

        // Selesai Kamis = 3 HK terpakai dari jatah 1 -> telat 2 hari.
        $telat = $this->sla()->evaluasiLog($buat('2026-01-08 09:00:00'));
        $this->assertSame(SlaCalculator::STATE_SELESAI_TERLAMBAT, $telat['state']);
        $this->assertSame('Selesai telat 2 hari', $telat['label']);

        // Selesai Selasa (hari terakhir) -> tepat waktu.
        $tepat = $this->sla()->evaluasiLog($buat('2026-01-06 23:00:00'));
        $this->assertSame(SlaCalculator::STATE_SELESAI, $tepat['state']);

        // Selesai di hari yang sama -> lebih awal.
        $awal = $this->sla()->evaluasiLog($buat('2026-01-05 16:00:00'));
        $this->assertSame(SlaCalculator::STATE_SELESAI_LEBIH_AWAL, $awal['state']);
    }

    public function test_transisi_mengisi_deadline_dan_mengosongkannya_saat_clock_off(): void
    {
        SlaConfig::create(['kode_tahap' => 'proses_evaluasi', 'nama_tahap' => 'Evaluasi', 'durasi' => 7, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);
        SlaConfig::create(['kode_tahap' => 'revisi_1', 'nama_tahap' => 'Revisi 1', 'durasi' => null, 'satuan' => 'hari_kerja', 'clock_off' => true, 'is_active' => true]);

        $permohonan = $this->permohonan('didisposisikan');
        $svc = new \App\Services\StatusTransitionService();

        try {
            Carbon::setTestNow('2026-01-05 14:00:00'); // Senin
            $svc->transisi($permohonan, Permohonan::STATUS_PROSES_EVALUASI);
            $permohonan->refresh();

            // Senin + 7 HK = Rabu 14 Jan.
            $this->assertSame('2026-01-14', $permohonan->sla_deadline_current->toDateString());
            $this->assertFalse($permohonan->is_overdue);

            // Masuk revisi = clock-off: tidak ada deadline yang berjalan.
            $svc->transisi($permohonan, Permohonan::STATUS_REVISI_1);
            $permohonan->refresh();
            $this->assertNull($permohonan->sla_deadline_current);
            $this->assertFalse($permohonan->is_overdue);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reminder_memilih_permohonan_mendekati_batas_dan_menandai_yang_telat(): void
    {
        SlaConfig::create(['kode_tahap' => 'proses_evaluasi', 'nama_tahap' => 'Evaluasi', 'durasi' => 7, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);

        $role = \App\Models\Role::firstOrCreate(['kode' => 'staff_sertifikasi'], ['nama' => 'Staff Sertifikasi']);
        // Staff uji sengaja tanpa no. WA: tidak ada pesan yang benar-benar terkirim.
        $staff = \App\Models\User::create([
            'role_id' => $role->id,
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => 'Staff Uji',
            'email' => uniqid() . '@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        $buat = function (string $mulai) use ($staff) {
            $p = $this->permohonan('proses_evaluasi');
            StatusLog::create(['permohonan_id' => $p->id, 'status' => 'proses_evaluasi', 'waktu_mulai' => Carbon::parse($mulai), 'is_clock_off' => false]);
            \App\Models\Distribusi::create([
                'permohonan_id' => $p->id,
                'ketua_tim_id' => $staff->id,
                'staff_id' => $staff->id,
                'jenis' => 'distribusi_awal',
                'is_aktif' => true,
                'tanggal' => now(),
            ]);

            return $p;
        };

        try {
            Carbon::setTestNow('2026-01-14 10:00:00'); // Rabu
            $longgar = $buat('2026-01-12 09:00:00');   // deadline 21 Jan, sisa 5
            $mepet = $buat('2026-01-05 14:00:00');     // deadline 14 Jan (hari ini), sisa 0
            $besok = $buat('2026-01-06 08:00:00');     // deadline 15 Jan, sisa 1
            $telat = $buat('2026-01-01 09:00:00');     // deadline 12 Jan, sudah lewat

            // Tanpa --dry-run agar kolom ikut disegarkan.
            $this->artisan('notifikasi:sla-reminder')
                ->expectsOutputToContain('Ditemukan 2 permohonan')
                ->assertSuccessful();

            $this->assertSame('2026-01-21', $longgar->refresh()->sla_deadline_current->toDateString());
            $this->assertSame('2026-01-14', $mepet->refresh()->sla_deadline_current->toDateString());
            $this->assertSame('2026-01-15', $besok->refresh()->sla_deadline_current->toDateString());
            $this->assertFalse($mepet->is_overdue);
            $this->assertTrue($telat->refresh()->is_overdue);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_command_hitung_ulang_menyeragamkan_durasi_lama(): void
    {
        SlaConfig::create(['kode_tahap' => 'proses_evaluasi', 'nama_tahap' => 'Evaluasi', 'durasi' => 7, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);
        $permohonan = $this->permohonan('proses_evaluasi');

        // Senin 14:00 -> Selasa 09:00. Rumus lama (per 24 jam) menyimpan 0; aturan baru = 1.
        $lama = StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'didisposisikan',
            'waktu_mulai' => Carbon::parse('2026-01-05 14:00:00'),
            'waktu_selesai' => Carbon::parse('2026-01-06 09:00:00'),
            'is_clock_off' => false,
            'durasi_hari_kerja' => 0,
        ]);
        StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'proses_evaluasi',
            'waktu_mulai' => Carbon::parse('2026-01-06 09:00:00'),
            'is_clock_off' => false,
        ]);

        try {
            Carbon::setTestNow('2026-01-07 10:00:00');

            // Dry-run tidak boleh menyimpan apa pun.
            $this->artisan('sla:hitung-ulang', ['--dry-run' => true])->assertSuccessful();
            $this->assertSame(0, $lama->refresh()->durasi_hari_kerja);
            $this->assertNull($permohonan->refresh()->sla_deadline_current);

            $this->artisan('sla:hitung-ulang')->assertSuccessful();
            $lama->refresh();
            $this->assertSame(1, $lama->durasi_hari_kerja);
            $this->assertSame('2026-01-05 14:00:00', $lama->waktu_mulai->format('Y-m-d H:i:s'));
            $this->assertSame('2026-01-06 09:00:00', $lama->waktu_selesai->format('Y-m-d H:i:s'));
            // Selasa + 7 HK = Kamis 15 Jan.
            $this->assertSame('2026-01-15', $permohonan->refresh()->sla_deadline_current->toDateString());
            $this->assertDatabaseHas('audit_trail', ['aksi' => 'sla_hitung_ulang']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_tahap_revisi_berstatus_clock_off(): void
    {
        SlaConfig::create(['kode_tahap' => 'revisi_1', 'nama_tahap' => 'Revisi 1', 'durasi' => null, 'satuan' => 'hari_kerja', 'clock_off' => true, 'is_active' => true]);

        $permohonan = $this->permohonan('revisi_1');
        $log = StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'revisi_1',
            'waktu_mulai' => now()->subDays(10),
            'is_clock_off' => true,
        ]);

        $hasil = $this->sla()->evaluasiLog($log);

        // Meski sudah 10 hari, tahap revisi TIDAK boleh dihitung sebagai keterlambatan.
        $this->assertSame(SlaCalculator::STATE_CLOCK_OFF, $hasil['state']);
        $this->assertTrue($hasil['clock_off']);
    }

    public function test_tahap_melewati_deadline_berstatus_late(): void
    {
        // Evaluasi 7 hari kerja; mulai jauh di masa lalu -> pasti telat.
        SlaConfig::create(['kode_tahap' => 'proses_evaluasi', 'nama_tahap' => 'Evaluasi', 'durasi' => 7, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);

        $permohonan = $this->permohonan('proses_evaluasi');
        $log = StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'proses_evaluasi',
            'waktu_mulai' => Carbon::parse('2026-01-01 09:00:00'),
            'is_clock_off' => false,
        ]);

        $hasil = $this->sla()->evaluasiLog($log);

        $this->assertSame(SlaCalculator::STATE_LATE, $hasil['state']);
        $this->assertLessThan(0, $hasil['sisa']);
    }

    public function test_tahap_masih_dalam_batas_berstatus_on_time(): void
    {
        SlaConfig::create(['kode_tahap' => 'proses_evaluasi', 'nama_tahap' => 'Evaluasi', 'durasi' => 7, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);

        $permohonan = $this->permohonan('proses_evaluasi');
        $log = StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => 'proses_evaluasi',
            'waktu_mulai' => now(),
            'is_clock_off' => false,
        ]);

        $hasil = $this->sla()->evaluasiLog($log);

        $this->assertSame(SlaCalculator::STATE_ON_TIME, $hasil['state']);
        $this->assertSame(7, $hasil['sisa']);
    }

    public function test_permohonan_ditutup_dinilai_dari_evaluasi_terakhir(): void
    {
        SlaConfig::create(['kode_tahap' => 'proses_evaluasi', 'nama_tahap' => 'Proses Evaluasi', 'durasi' => 7, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);
        SlaConfig::create(['kode_tahap' => 'menunggu_surat_pengesahan', 'nama_tahap' => 'Menunggu Surat', 'durasi' => 3, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true]);
        $sla = $this->sla();

        $log = fn (Permohonan $p, string $status, string $mulai, ?string $selesai) => StatusLog::create([
            'permohonan_id' => $p->id,
            'status' => $status,
            'waktu_mulai' => Carbon::parse($mulai),
            'waktu_selesai' => $selesai ? Carbon::parse($selesai) : null,
            'durasi_hari_kerja' => $selesai ? $sla->hitungHariKerja($mulai, $selesai) : null,
            'is_clock_off' => false,
        ]);

        // Penutupan normal: proses_evaluasi (2 HK dari 7) -> ditutup.
        $normal = $this->permohonan(Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG);
        $log($normal, 'proses_evaluasi', '2026-01-05 09:00:00', '2026-01-07 09:00:00');
        $log($normal, 'ditutup_pengajuan_ulang', '2026-01-07 09:00:00', null);

        $hasilNormal = $sla->evaluasiPermohonan($normal->load('statusLog'));
        $this->assertSame(SlaCalculator::STATE_SELESAI_LEBIH_AWAL, $hasilNormal['state']);

        // Hasil koreksi: proses_evaluasi sama, lalu menunggu_surat keliru 15 HK, lalu ditutup.
        // Harus dinilai sama dengan penutupan normal, bukan "telat" dari tahap keliru.
        $koreksi = $this->permohonan(Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG);
        $log($koreksi, 'proses_evaluasi', '2026-01-05 09:00:00', '2026-01-07 09:00:00');
        $log($koreksi, 'menunggu_surat_pengesahan', '2026-01-07 09:00:00', '2026-01-28 09:00:00');
        $log($koreksi, 'ditutup_pengajuan_ulang', '2026-01-28 09:00:00', null);

        $hasilKoreksi = $sla->evaluasiPermohonan($koreksi->load('statusLog'));
        $this->assertSame($hasilNormal['state'], $hasilKoreksi['state']);
        $this->assertSame($hasilNormal['label'], $hasilKoreksi['label']);
    }

    private function permohonan(string $status): Permohonan
    {
        $pbf = \App\Models\Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '0800' . random_int(10000, 99999),
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => false,
        ]);

        return Permohonan::create([
            'no_registrasi' => 'TST/' . uniqid(),
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PBF Uji',
            'nib_snapshot' => '123',
            'email_snapshot' => 'uji@pbf.test',
            'no_wa_snapshot' => '0800',
            'status_saat_ini' => $status,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
    }
}
