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
