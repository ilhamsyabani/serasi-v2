<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\Pbf;
use App\Models\Permohonan;
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

    public function test_detail_route_menolak_tamu_dengan_redirect_login(): void
    {
        $p = $this->permohonan();

        $this->get("/admin/permohonan/{$p->id}")->assertRedirect(route('internal.login'));
    }
}
