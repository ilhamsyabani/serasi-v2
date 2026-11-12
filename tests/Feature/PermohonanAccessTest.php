<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\DokumenRevisi;
use App\Models\Evaluasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Revisi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi Bug A3: detail permohonan sempat 403 untuk Katim & Staff, dan
 * permohonan pengajuan-ulang (kepala_balai_id NULL) tak bisa dibuka Kabalai.
 */
class PermohonanAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function user(string $kode): User
    {
        return User::create([
            'role_id' => Role::where('kode', $kode)->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => ucfirst($kode),
            'email' => uniqid() . '@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);
    }

    private function permohonan(array $attr = []): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '08001',
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => false,
        ]);

        return Permohonan::create(array_merge([
            'no_registrasi' => 'TST/' . uniqid(),
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PBF Uji',
            'nib_snapshot' => '123',
            'email_snapshot' => 'uji@pbf.test',
            'no_wa_snapshot' => '0800',
            'status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ], $attr));
    }

    public function test_ketua_tim_yang_didisposisikan_bisa_lihat(): void
    {
        $katim = $this->user('ketua_tim');
        $p = $this->permohonan();
        Disposisi::create([
            'permohonan_id' => $p->id,
            'kepala_balai_id' => $this->user('kepala_balai')->id,
            'ketua_tim_id' => $katim->id,
            'tanggal_disposisi' => now(),
        ]);

        $this->assertTrue($katim->can('view', $p->fresh()));
    }

    public function test_staff_yang_ditugaskan_bisa_lihat(): void
    {
        $staff = $this->user('staff_sertifikasi');
        $p = $this->permohonan();
        Distribusi::create([
            'permohonan_id' => $p->id,
            'ketua_tim_id' => $this->user('ketua_tim')->id,
            'staff_id' => $staff->id,
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => now(),
        ]);

        $this->assertTrue($staff->can('view', $p->fresh()));
    }

    public function test_staff_lain_tidak_bisa_lihat(): void
    {
        $staffLain = $this->user('staff_sertifikasi');
        $p = $this->permohonan();

        $this->assertFalse($staffLain->can('view', $p));
    }

    public function test_kepala_balai_bisa_lihat_pengajuan_ulang_tanpa_pemilik(): void
    {
        // Pengajuan ulang mandiri pemohon: kepala_balai_id NULL.
        $kabalai = $this->user('kepala_balai');
        $p = $this->permohonan(['kepala_balai_id' => null, 'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_PEMOHON]);

        $this->assertTrue($kabalai->can('view', $p));
    }

    public function test_admin_it_bisa_lihat_semua(): void
    {
        $this->assertTrue($this->user('admin_it')->can('view', $this->permohonan()));
    }

    public function test_staff_melihat_catatan_dan_dokumen_revisi_di_detail(): void
    {
        $staff = $this->user('staff_sertifikasi');
        $p = $this->permohonan(['status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI, 'revisi_ke' => 2]);
        Distribusi::create([
            'permohonan_id' => $p->id,
            'ketua_tim_id' => $this->user('ketua_tim')->id,
            'staff_id' => $staff->id,
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => now(),
        ]);

        $ev0 = Evaluasi::create([
            'permohonan_id' => $p->id, 'staff_id' => $staff->id, 'siklus_ke' => 0,
            'hasil' => Evaluasi::HASIL_TIDAK_LENGKAP, 'catatan' => 'Denah gudang belum jelas', 'tanggal_evaluasi' => now(),
        ]);
        $revisi = Revisi::create(['evaluasi_id' => $ev0->id, 'permohonan_id' => $p->id]);
        DokumenRevisi::create([
            'revisi_id' => $revisi->id, 'jenis_dokumen' => 'denah', 'nama_file_asli' => 'denah-revisi-1.pdf',
            'path_file' => 'dokumen_revisi/x.pdf', 'ukuran_file_kb' => 120, 'mime_type' => 'application/pdf',
            'checksum' => 'abc', 'uploaded_at' => now(),
        ]);
        Evaluasi::create([
            'permohonan_id' => $p->id, 'staff_id' => $staff->id, 'siklus_ke' => 1,
            'hasil' => Evaluasi::HASIL_TIDAK_LENGKAP, 'catatan' => 'Ukuran ruang karantina kurang', 'tanggal_evaluasi' => now(),
        ]);

        $this->actingAs($staff)->get(route('internal.permohonan.show', $p))
            ->assertOk()
            ->assertSee('Riwayat Revisi')
            ->assertSee('Denah gudang belum jelas')
            ->assertSee('denah-revisi-1.pdf')
            ->assertSee('Revisi ke-2')
            ->assertSee('Ukuran ruang karantina kurang')
            ->assertSee('Menunggu perbaikan dari pemohon');
    }

    public function test_kabalai_melihat_staff_evaluator_di_daftar_dan_detail(): void
    {
        $kabalai = $this->user('kepala_balai');
        $staff = $this->user('staff_sertifikasi');
        $staff->update(['nama' => 'Siti Evaluator']);
        $p = $this->permohonan(['kepala_balai_id' => $kabalai->id]);
        Distribusi::create([
            'permohonan_id' => $p->id,
            'ketua_tim_id' => $this->user('ketua_tim')->id,
            'staff_id' => $staff->id,
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => now(),
        ]);
        $belumDidistribusi = $this->permohonan(['kepala_balai_id' => $kabalai->id]);

        $this->actingAs($kabalai)->get(route('internal.kabalai.permohonan.index'))
            ->assertOk()
            ->assertSee('Staff Evaluator')
            ->assertSee('Siti Evaluator')
            ->assertSee('Belum ditentukan');

        $this->actingAs($kabalai)->get(route('internal.kabalai.permohonan.show', $p))
            ->assertOk()
            ->assertSee('Siti Evaluator');

        $this->actingAs($kabalai)->get(route('internal.kabalai.permohonan.show', $belumDidistribusi))
            ->assertOk()
            ->assertSee('Belum ditentukan');
    }

    public function test_dashboard_katim_dan_staff_dipaginasi(): void
    {
        $kabalai = $this->user('kepala_balai');
        $katim = $this->user('ketua_tim');
        $staff = $this->user('staff_sertifikasi');

        foreach (range(1, 12) as $i) {
            $p = $this->permohonan([
                'no_registrasi' => sprintf('PG/%02d', $i),
                'status_saat_ini' => Permohonan::STATUS_DIDISPOSISIKAN,
            ]);
            $p->forceFill(['created_at' => now()->subMinutes(100 - $i)])->save();
            Disposisi::create([
                'permohonan_id' => $p->id, 'kepala_balai_id' => $kabalai->id,
                'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now(),
            ]);
            Distribusi::create([
                'permohonan_id' => $p->id, 'ketua_tim_id' => $katim->id, 'staff_id' => $staff->id,
                'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now(),
            ]);
        }

        foreach ([[$katim, 'internal.ketua_tim.dashboard'], [$staff, 'internal.staff.dashboard']] as [$u, $route]) {
            $hal1 = $this->actingAs($u)->get(route($route))->assertOk();
            $this->assertCount(10, $hal1->viewData('permohonans'));
            $hal1->assertSee('PG/12')->assertDontSee('PG/01');

            $hal2 = $this->actingAs($u)->get(route($route, ['page' => 2]))->assertOk();
            $this->assertCount(2, $hal2->viewData('permohonans'));
            $hal2->assertSee('PG/01')->assertDontSee('PG/12');
        }

        $this->assertCount(12, $this->actingAs($katim)->get(route('internal.ketua_tim.dashboard'))->viewData('perluDistribusi'));
    }

    public function test_dashboard_katim_dan_staff_filter_status_dan_tanggal(): void
    {
        $kabalai = $this->user('kepala_balai');
        $katim = $this->user('ketua_tim');
        $staff = $this->user('staff_sertifikasi');

        $data = [
            ['FL/LAMA', Permohonan::STATUS_PROSES_EVALUASI, '2026-01-10'],
            ['FL/REVISI', Permohonan::STATUS_REVISI_1, '2026-03-15'],
            ['FL/EVAL', Permohonan::STATUS_PROSES_EVALUASI, '2026-03-20'],
        ];
        foreach ($data as [$noReg, $status, $tgl]) {
            $p = $this->permohonan(['no_registrasi' => $noReg, 'status_saat_ini' => $status, 'tanggal_pengajuan' => $tgl]);
            Disposisi::create([
                'permohonan_id' => $p->id, 'kepala_balai_id' => $kabalai->id,
                'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now(),
            ]);
            Distribusi::create([
                'permohonan_id' => $p->id, 'ketua_tim_id' => $katim->id, 'staff_id' => $staff->id,
                'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now(),
            ]);
        }

        $filter = ['status' => Permohonan::STATUS_PROSES_EVALUASI, 'tanggal_dari' => '2026-03-01', 'tanggal_sampai' => '2026-03-31'];

        foreach ([[$katim, 'internal.ketua_tim.dashboard'], [$staff, 'internal.staff.dashboard']] as [$u, $route]) {
            $hasil = $this->actingAs($u)->get(route($route, $filter))->assertOk()->viewData('permohonans');
            $this->assertSame(['FL/EVAL'], $hasil->pluck('no_registrasi')->all());
            $this->assertStringContainsString('tanggal_dari=2026-03-01', $hasil->url(2));
        }
    }

    public function test_detail_route_menolak_tamu_dengan_redirect_login(): void
    {
        $p = $this->permohonan();

        $this->get("/admin/permohonan/{$p->id}")->assertRedirect(route('internal.login'));
    }
}
