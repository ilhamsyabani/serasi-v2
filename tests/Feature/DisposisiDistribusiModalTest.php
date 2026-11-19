<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Form disposisi (Kepala Balai) & distribusi (Ketua Tim) tampil sebagai modal:
 * satu modal per halaman, dibuka dari tombol di tiap baris, dan dibuka ulang
 * otomatis bila validasi gagal.
 */
class DisposisiDistribusiModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_halaman_disposisi_memakai_modal(): void
    {
        $kabalai = $this->user('kepala_balai');
        $p = $this->permohonan(Permohonan::STATUS_PENGAJUAN, ['kepala_balai_id' => $kabalai->id]);

        $html = $this->actingAs($kabalai)->get(route('internal.kabalai.disposisi.index'))->assertOk()->getContent();

        $this->assertStringContainsString("\$dispatch('buka-disposisi'", $html);
        $this->assertStringContainsString($p->uuid, $html);
        // Tombol wajib di dalam x-data, kalau tidak Alpine mengabaikan x-on:click (modal tak muncul).
        $this->assertMatchesRegularExpression('/<td[^>]*\sx-data>\s*<button[^>]*buka-disposisi/', $html);
        $this->assertSame(1, substr_count($html, 'x-on:buka-disposisi.window'), 'Hanya satu modal per halaman.');
        $this->assertStringContainsString('buka: false', $html);
    }

    public function test_disposisi_gagal_validasi_membuka_ulang_modal(): void
    {
        $kabalai = $this->user('kepala_balai');
        $bukanKatim = $this->user('staff_sertifikasi');
        $p = $this->permohonan(Permohonan::STATUS_PENGAJUAN, ['kepala_balai_id' => $kabalai->id]);

        $this->actingAs($kabalai)
            ->from(route('internal.kabalai.disposisi.index'))
            ->post(route('internal.kabalai.disposisi.store', $p), [
                'ketua_tim_id' => $bukanKatim->id,
                'catatan' => 'Catatan uji',
                '_permohonan' => $p->uuid,
                '_no_reg' => $p->no_registrasi,
                '_pbf' => $p->nama_pbf_snapshot,
            ])
            ->assertRedirect(route('internal.kabalai.disposisi.index'))
            ->assertSessionHasErrors('ketua_tim_id');

        $html = $this->actingAs($kabalai)->get(route('internal.kabalai.disposisi.index'))->getContent();

        $this->assertStringContainsString('buka: true', $html);
        $this->assertStringContainsString('Ketua Tim yang dipilih tidak valid', $html);
        $this->assertStringContainsString('Catatan uji', $html);
        $this->assertSame(Permohonan::STATUS_PENGAJUAN, $p->refresh()->status_saat_ini);
    }

    public function test_disposisi_dari_modal_berhasil(): void
    {
        $kabalai = $this->user('kepala_balai');
        $katim = $this->user('ketua_tim');
        $p = $this->permohonan(Permohonan::STATUS_PENGAJUAN, ['kepala_balai_id' => $kabalai->id]);

        $this->actingAs($kabalai)
            ->from(route('internal.kabalai.disposisi.index'))
            ->post(route('internal.kabalai.disposisi.store', $p), [
                'ketua_tim_id' => $katim->id,
                '_permohonan' => $p->uuid,
                '_no_reg' => $p->no_registrasi,
                '_pbf' => $p->nama_pbf_snapshot,
            ])
            ->assertSessionHas('success');

        $this->assertSame(Permohonan::STATUS_DIDISPOSISIKAN, $p->refresh()->status_saat_ini);
    }

    public function test_halaman_distribusi_memakai_modal_dan_membuka_ulang_saat_gagal(): void
    {
        $katim = $this->user('ketua_tim');
        $staff = $this->user('staff_sertifikasi');
        $p = $this->permohonan(Permohonan::STATUS_DIDISPOSISIKAN);
        Disposisi::create(['permohonan_id' => $p->id, 'kepala_balai_id' => $katim->id, 'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now()]);

        $html = $this->actingAs($katim)->get(route('internal.ketua_tim.distribusi.index'))->assertOk()->getContent();
        $this->assertStringContainsString("\$dispatch('buka-distribusi'", $html);
        $this->assertStringContainsString($p->uuid, $html);
        $this->assertMatchesRegularExpression('/<td[^>]*\sx-data>\s*<button[^>]*buka-distribusi/', $html);
        $this->assertSame(1, substr_count($html, 'x-on:buka-distribusi.window'));

        // Gagal: Ketua Tim LAIN bukan evaluator yang sah (Ketua Tim hanya boleh menugaskan dirinya sendiri).
        $katimLain = $this->user('ketua_tim');
        $this->actingAs($katim)
            ->from(route('internal.ketua_tim.distribusi.index'))
            ->post(route('internal.ketua_tim.distribusi.store', $p), [
                'staff_id' => $katimLain->id,
                '_permohonan' => $p->uuid,
                '_no_reg' => $p->no_registrasi,
                '_pbf' => $p->nama_pbf_snapshot,
            ])
            ->assertSessionHasErrors('staff_id');

        $html = $this->actingAs($katim)->get(route('internal.ketua_tim.distribusi.index'))->getContent();
        $this->assertStringContainsString('buka: true', $html);
        $this->assertStringContainsString('Staff yang dipilih tidak valid', $html);

        // Berhasil.
        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $staff->id])
            ->assertSessionHas('success');
        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->refresh()->status_saat_ini);
    }

    private function user(string $kode): User
    {
        return User::create([
            'role_id' => Role::where('kode', $kode)->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => ucfirst($kode) . ' ' . uniqid(),
            'email' => uniqid() . '@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);
    }

    private function permohonan(string $status, array $attr = []): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '0812' . random_int(1000000, 9999999),
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);

        $p = Permohonan::create(array_merge([
            'no_registrasi' => 'TST/' . uniqid(),
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PBF Uji',
            'nib_snapshot' => $pbf->nib,
            'email_snapshot' => $pbf->email,
            'no_wa_snapshot' => $pbf->no_whatsapp,
            'status_saat_ini' => $status,
            'revisi_ke' => 0,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ], $attr));

        StatusLog::create(['permohonan_id' => $p->id, 'status' => $status, 'waktu_mulai' => now(), 'is_clock_off' => false]);

        return $p;
    }
}
