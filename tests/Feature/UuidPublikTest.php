<?php

namespace Tests\Feature;

use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UuidPublikTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_v4_dibuat_otomatis_dan_id_tetap_angka(): void
    {
        $p = $this->permohonan();

        $this->assertIsInt($p->id);
        $this->assertTrue(Str::isUuid($p->uuid));
        $this->assertSame('4', $p->uuid[14], 'Harus UUID v4 (acak), bukan v7 berbasis waktu.');
        $this->assertSame('uuid', $p->getRouteKeyName());
    }

    public function test_url_memakai_uuid_bukan_id(): void
    {
        $p = $this->permohonan();

        $url = route('internal.permohonan.show', $p);

        $this->assertStringEndsWith('/' . $p->uuid, $url);
        $this->assertStringNotContainsString('/permohonan/' . $p->id, $url);
    }

    public function test_akses_dengan_id_angka_ditolak_404(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $p = $this->permohonan();
        $admin = User::create([
            'role_id' => Role::where('kode', 'admin_it')->value('id'),
            'nip' => '1',
            'nama' => 'Admin Uji',
            'email' => 'admin@uji.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        $this->actingAs($admin)->get('/admin/permohonan/' . $p->id)->assertNotFound();
        $this->actingAs($admin)->get('/admin/permohonan/' . Str::uuid())->assertNotFound();
    }

    public function test_pemohon_lain_tetap_ditolak_walau_tahu_uuid(): void
    {
        $milikA = $this->permohonan();
        $pbfB = $this->pbf();

        $this->actingAs($pbfB, 'pemohon')
            ->get(route('pemohon.permohonan.show', $milikA))
            ->assertForbidden();
    }

    private function pbf(): Pbf
    {
        return Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '0800' . random_int(10000, 99999),
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);
    }

    private function permohonan(): Permohonan
    {
        return Permohonan::create([
            'no_registrasi' => 'UUID/' . uniqid(),
            'pbf_id' => $this->pbf()->id,
            'nama_pbf_snapshot' => 'PBF Uji',
            'nib_snapshot' => '123',
            'email_snapshot' => 'uji@pbf.test',
            'no_wa_snapshot' => '0800',
            'status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
    }
}
