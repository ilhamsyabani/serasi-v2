<?php

namespace Tests\Feature;

use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Services\StatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aturan bisnis kritis CLAUDE.md §3 poin 2: maks 3 siklus revisi.
 * Interpretasi terkonfirmasi: revisi_1, revisi_2, revisi_3 boleh terjadi;
 * evaluasi gagal ke-4 -> ditutup_pengajuan_ulang (bukan revisi_4).
 */
class RevisiCycleTest extends TestCase
{
    use RefreshDatabase;

    private function service(): StatusTransitionService
    {
        return new StatusTransitionService();
    }

    private function permohonan(): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '0800',
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
            'status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI,
            'revisi_ke' => 0,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
    }

    /** Simulasi: setiap evaluasi gagal, permohonan kembali ke proses_evaluasi. */
    private function evaluasiGagalLaluKembali(StatusTransitionService $svc, Permohonan $p): string
    {
        $log = $svc->mintaRevisiAtauTutup($p, 'tidak lengkap', null);
        $p->refresh();
        if ($log->status !== Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG) {
            // Pemohon upload revisi -> kembali dievaluasi.
            $svc->transisi($p, Permohonan::STATUS_PROSES_EVALUASI, 'Revisi diupload');
            $p->refresh();
        }
        return $log->status;
    }

    public function test_tiga_revisi_penuh_lalu_ditutup_pada_evaluasi_keempat(): void
    {
        $svc = $this->service();
        $p = $this->permohonan();

        $this->assertSame(Permohonan::STATUS_REVISI_1, $this->evaluasiGagalLaluKembali($svc, $p));
        $this->assertSame(Permohonan::STATUS_REVISI_2, $this->evaluasiGagalLaluKembali($svc, $p));
        $this->assertSame(Permohonan::STATUS_REVISI_3, $this->evaluasiGagalLaluKembali($svc, $p));

        // Evaluasi gagal ke-4: kuota habis -> ditutup, BUKAN revisi_4.
        $this->assertSame(Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG, $this->evaluasiGagalLaluKembali($svc, $p));

        $this->assertFalse(
            StatusLog::where('permohonan_id', $p->id)->where('status', 'revisi_4')->exists(),
            'revisi_4 tidak boleh pernah tercatat.'
        );
    }

    public function test_revisi_ke_dipelihara_di_record_permohonan(): void
    {
        $svc = $this->service();
        $p = $this->permohonan();

        $svc->transisi($p, Permohonan::STATUS_REVISI_1, null);
        $this->assertSame(1, $p->fresh()->revisi_ke);

        $svc->transisi($p, Permohonan::STATUS_PROSES_EVALUASI, null);
        $svc->transisi($p, Permohonan::STATUS_REVISI_2, null);
        $this->assertSame(2, $p->fresh()->revisi_ke);
    }

    public function test_transisi_ke_revisi_melebihi_batas_ditolak(): void
    {
        $svc = $this->service();
        $p = $this->permohonan();

        $this->expectException(\RuntimeException::class);
        $svc->transisi($p, 'revisi_4', null);
    }

    public function test_status_terminal_tercapai_bukan_selesai(): void
    {
        // Regresi CLAUDE.md §3 poin 4: status akhir sukses = terbit_surat_pengesahan.
        $svc = $this->service();
        $p = $this->permohonan();

        $svc->transisi($p, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, null);
        $svc->transisi($p, Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN, null);

        $this->assertTrue($p->fresh()->isStatusAkhir());
        $this->assertSame(Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN, $p->fresh()->status_saat_ini);
    }
}
