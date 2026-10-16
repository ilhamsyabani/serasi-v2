<?php

namespace Tests\Feature;

use App\Models\Distribusi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluasiDoubleSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_evaluasi_hanya_bisa_dilakukan_saat_proses_evaluasi(): void
    {
        $staff = User::create([
            'role_id' => Role::where('kode', Role::STAFF_SERTIFIKASI)->value('id'),
            'nip' => '12345678',
            'nama' => 'Staff Uji',
            'email' => 'staff@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        $pbf = Pbf::create([
            'nib' => '1234567890123',
            'nama_pbf' => 'PBF Uji',
            'email' => 'uji@pbf.test',
            'no_whatsapp' => '08123456789',
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => false,
        ]);

        $p = Permohonan::create([
            'no_registrasi' => 'TST/001',
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PBF Uji',
            'nib_snapshot' => '1234567890123',
            'email_snapshot' => 'uji@pbf.test',
            'no_wa_snapshot' => '08123456789',
            'status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI,
            'revisi_ke' => 0,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);

        $katim = User::create([
            'role_id' => Role::where('kode', Role::KETUA_TIM)->value('id'),
            'nip' => '87654321',
            'nama' => 'Katim Uji',
            'email' => 'katim@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        Distribusi::create([
            'permohonan_id' => $p->id,
            'ketua_tim_id' => $katim->id,
            'staff_id' => $staff->id,
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => now(),
        ]);

        // Submit evaluasi pertama (tidak lengkap)
        $response1 = $this->actingAs($staff)->put(route('internal.staff.evaluasi.update', $p), [
            'hasil' => 'tidak_lengkap',
            'catatan' => 'Perlu perbaikan denah',
            'siklus_ke' => 1,
        ]);

        $response1->assertRedirect(route('internal.staff.dashboard'));
        $p->refresh();
        $this->assertSame(Permohonan::STATUS_REVISI_1, $p->status_saat_ini);
        $this->assertSame(1, $p->revisi_ke);

        // Submit kedua (simulasi double click / request berulang tanpa reload dengan siklus_ke yang sama)
        $response2 = $this->actingAs($staff)->put(route('internal.staff.evaluasi.update', $p), [
            'hasil' => 'tidak_lengkap',
            'catatan' => 'Perlu perbaikan denah',
            'siklus_ke' => 1,
        ]);

        $response2->assertStatus(422);
        $p->refresh();
        // Pastikan status TIDAK loncat ke revisi_2!
        $this->assertSame(Permohonan::STATUS_REVISI_1, $p->status_saat_ini);
        $this->assertSame(1, $p->revisi_ke);
    }

    public function test_bisa_evaluasi_pada_status_revisi_3(): void
    {
        $staff = User::create([
            'role_id' => Role::where('kode', Role::STAFF_SERTIFIKASI)->value('id'),
            'nip' => '12345679',
            'nama' => 'Staff Uji 2',
            'email' => 'staff2@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        $pbf = Pbf::create([
            'nib' => '9999999999999',
            'nama_pbf' => 'PBF Uji 2',
            'email' => 'uji2@pbf.test',
            'no_whatsapp' => '08123456780',
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => false,
        ]);

        $p = Permohonan::create([
            'no_registrasi' => 'TST/002',
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PBF Uji 2',
            'nib_snapshot' => '9999999999999',
            'email_snapshot' => 'uji2@pbf.test',
            'no_wa_snapshot' => '08123456780',
            'status_saat_ini' => Permohonan::STATUS_REVISI_3,
            'revisi_ke' => 3,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);

        $katim = User::create([
            'role_id' => Role::where('kode', Role::KETUA_TIM)->value('id'),
            'nip' => '87654322',
            'nama' => 'Katim Uji 2',
            'email' => 'katim2@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        Distribusi::create([
            'permohonan_id' => $p->id,
            'ketua_tim_id' => $katim->id,
            'staff_id' => $staff->id,
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => now(),
        ]);

        // Halaman edit tidak boleh 403 saat status_saat_ini adalah revisi_3
        $responseEdit = $this->actingAs($staff)->get(route('internal.staff.evaluasi.edit', $p));
        $responseEdit->assertOk();
    }
}
