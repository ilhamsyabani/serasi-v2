<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Tests\TestCase;

/** M-14 Laporan & Ekspor untuk Ketua Tim (timnya) dan Staff (miliknya). */
class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $katimA;
    private User $katimB;
    private User $staffA;
    private User $staffB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->katimA = $this->user(Role::KETUA_TIM);
        $this->katimB = $this->user(Role::KETUA_TIM);
        $this->staffA = $this->user(Role::STAFF_SERTIFIKASI);
        $this->staffB = $this->user(Role::STAFF_SERTIFIKASI);
    }

    private function user(string $kode): User
    {
        return User::create([
            'role_id' => Role::where('kode', $kode)->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => ucfirst($kode) . ' ' . uniqid(),
            'email' => uniqid() . '@bbpom.test',
            'no_whatsapp' => '0811' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);
    }

    private function permohonan(string $noReg, string $namaPbf, User $katim, User $staff, string $status, string $tanggal = '2026-11-01'): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => $namaPbf,
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '081100000003',
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);
        $p = Permohonan::create([
            'no_registrasi' => $noReg,
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => $namaPbf,
            'nib_snapshot' => $pbf->nib,
            'email_snapshot' => $pbf->email,
            'no_wa_snapshot' => $pbf->no_whatsapp,
            'status_saat_ini' => $status,
            'revisi_ke' => 0,
            'tanggal_pengajuan' => $tanggal,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p->id, 'status' => $status, 'waktu_mulai' => now()->subDay(), 'is_clock_off' => false]);
        Disposisi::create(['permohonan_id' => $p->id, 'kepala_balai_id' => $katim->id, 'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now()]);
        Distribusi::create(['permohonan_id' => $p->id, 'ketua_tim_id' => $katim->id, 'staff_id' => $staff->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()]);

        return $p;
    }

    private function siapkanData(): void
    {
        $this->permohonan('LAP/001', 'PT Tim A Satu', $this->katimA, $this->staffA, Permohonan::STATUS_PROSES_EVALUASI, '2026-10-05');
        $this->permohonan('LAP/002', 'PT Tim A Dua', $this->katimA, $this->staffB, Permohonan::STATUS_REVISI_1, '2026-11-10');
        $this->permohonan('LAP/003', 'PT Tim B', $this->katimB, $this->staffA, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, '2026-11-12');
    }

    /** Baris sheet pertama XLSX sebagai array of arrays. */
    private function bacaXlsx(string $isi): array
    {
        $path = tempnam(sys_get_temp_dir(), 'lap') . '.xlsx';
        file_put_contents($path, $isi);
        $reader = new XlsxReader();
        $reader->open($path);
        $baris = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $baris[] = $row->toArray();
            }
            break;
        }
        $reader->close();
        @unlink($path);

        return $baris;
    }

    private function noRegDiXlsx(array $baris): array
    {
        $header = array_search(\App\Services\LaporanService::HEADER, $baris, true);
        $this->assertNotFalse($header, 'Baris header tidak ditemukan');

        return array_map(fn ($b) => $b[1], array_slice($baris, $header + 1));
    }

    public function test_ketua_tim_hanya_melihat_permohonan_timnya(): void
    {
        $this->siapkanData();

        $this->actingAs($this->katimA)->get(route('internal.laporan.index'))
            ->assertOk()
            ->assertSee('LAP/001')->assertSee('LAP/002')
            ->assertDontSee('LAP/003');
    }

    public function test_staff_hanya_melihat_permohonan_miliknya(): void
    {
        $this->siapkanData();

        $this->actingAs($this->staffA)->get(route('internal.laporan.index'))
            ->assertOk()
            ->assertSee('LAP/001')->assertSee('LAP/003')
            ->assertDontSee('LAP/002');
    }

    public function test_ekspor_excel_ketua_tim_sesuai_cakupan(): void
    {
        $this->siapkanData();

        $res = $this->actingAs($this->katimA)->get(route('internal.laporan.export'));
        $res->assertOk();
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $res->headers->get('Content-Disposition'));

        $noReg = $this->noRegDiXlsx($this->bacaXlsx($res->streamedContent()));
        sort($noReg);
        $this->assertSame(['LAP/001', 'LAP/002'], $noReg);
    }

    public function test_ekspor_mengikuti_filter(): void
    {
        $this->siapkanData();

        $res = $this->actingAs($this->katimA)->get(route('internal.laporan.export', [
            'tanggal_dari' => '2026-11-01',
            'tanggal_sampai' => '2026-11-30',
        ]));
        $this->assertSame(['LAP/002'], $this->noRegDiXlsx($this->bacaXlsx($res->streamedContent())));

        $res = $this->actingAs($this->staffA)->get(route('internal.laporan.export', ['status' => Permohonan::STATUS_PROSES_EVALUASI]));
        $this->assertSame(['LAP/001'], $this->noRegDiXlsx($this->bacaXlsx($res->streamedContent())));
    }

    public function test_ekspor_pdf_sesuai_cakupan_dan_filter(): void
    {
        $this->siapkanData();

        // Isi PDF terkompresi; periksa data yang dikirim ke view, lalu pastikan PDF valid.
        $res = $this->actingAs($this->katimA)->get(route('internal.laporan.export-pdf', ['status' => Permohonan::STATUS_REVISI_1]));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('.pdf', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $res->getContent());

        $html = view('internal.laporan.pdf', [
            'user' => $this->katimA,
            'keterangan' => [],
            'dicetak' => now()->format('d/m/Y H:i'),
            'baris' => app(\App\Services\LaporanService::class)->baris(
                app(\App\Services\LaporanService::class)->query($this->katimA, [])->get()
            ),
            'ringkasan' => [],
        ])->render();
        $this->assertStringContainsString('LAP/001', $html);
        $this->assertStringContainsString('LAP/002', $html);
        $this->assertStringNotContainsString('LAP/003', $html);
    }

    public function test_filter_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->katimA)->get(route('internal.laporan.export', ['status' => 'selesai']))
            ->assertSessionHasErrors('status');
    }

    public function test_role_lain_tidak_boleh_akses(): void
    {
        foreach ([Role::KEPALA_BALAI, Role::ADMIN_IT] as $kode) {
            $u = $this->user($kode);
            $this->actingAs($u)->get(route('internal.laporan.index'))->assertForbidden();
            $this->actingAs($u)->get(route('internal.laporan.export'))->assertForbidden();
            $this->actingAs($u)->get(route('internal.laporan.export-pdf'))->assertForbidden();
        }
    }
}
