<?php

namespace Tests\Feature;

use App\Mail\NotifikasiMail;
use App\Models\Notifikasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\TemplateNotifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotifikasiLogTest extends TestCase
{
    use RefreshDatabase;

    private User $adminIt;
    private User $kabalai;
    private User $staff;
    private Permohonan $permohonan;
    private Pbf $pbf;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $adminRole = Role::where('kode', 'admin_it')->first();
        $kabalaiRole = Role::where('kode', 'kepala_balai')->first();
        $staffRole = Role::where('kode', 'staff_sertifikasi')->first();

        $this->adminIt = User::create([
            'role_id' => $adminRole->id,
            'nip' => '199506072019032007',
            'nama' => 'Admin IT',
            'email' => 'admin.it@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        $this->kabalai = User::create([
            'role_id' => $kabalaiRole->id,
            'nip' => '198001012005011001',
            'nama' => 'Kepala Balai',
            'email' => 'kabalai@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        $this->staff = User::create([
            'role_id' => $staffRole->id,
            'nip' => '199001012015011002',
            'nama' => 'Staff Penguji',
            'email' => 'staff.penguji@bbpom.test',
            'no_whatsapp' => '081234567890',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);

        $this->pbf = Pbf::create([
            'nib' => '1234567890123',
            'nama_pbf' => 'PT Farmasi Sejahtera',
            'email' => 'pbf.sejahtera@test.com',
            'no_whatsapp' => '089876543210',
            'password_hash' => bcrypt('password'),
            'otp_terverifikasi' => true,
        ]);

        $this->permohonan = Permohonan::create([
            'no_registrasi' => 'PBF/DENAH/2026/99999',
            'pbf_id' => $this->pbf->id,
            'nama_pbf_snapshot' => $this->pbf->nama_pbf,
            'nib_snapshot' => $this->pbf->nib,
            'email_snapshot' => $this->pbf->email,
            'no_wa_snapshot' => $this->pbf->no_whatsapp,
            'status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
            'kepala_balai_id' => $this->kabalai->id,
        ]);

        TemplateNotifikasi::create([
            'kode_event' => 'DISTRIBUSI_BARU',
            'channel' => 'email',
            'subjek' => 'Permohonan Baru - Distribusi Staff',
            'isi_template' => 'Permohonan {{no_registrasi}} untuk {{nama_pbf}} telah didistribusikan.',
            'is_active' => true,
        ]);

        TemplateNotifikasi::create([
            'kode_event' => 'REVISI_DIMINTA',
            'channel' => 'email',
            'subjek' => 'Permintaan Revisi',
            'isi_template' => 'Permohonan {{no_registrasi}} memerlukan revisi.',
            'is_active' => true,
        ]);
    }

    public function test_admin_it_can_view_notifikasi_log_page(): void
    {
        $response = $this->actingAs($this->adminIt)->get(route('internal.adminit.notifikasi-log.index'));

        $response->assertStatus(200);
        $response->assertSee('Log Riwayat Notifikasi');
    }

    public function test_search_filter_in_notifikasi_log(): void
    {
        Notifikasi::create([
            'permohonan_id' => $this->permohonan->id,
            'tujuan_tipe' => Notifikasi::TUJUAN_PEMOHON,
            'tujuan_id' => $this->pbf->id,
            'channel' => Notifikasi::CHANNEL_EMAIL,
            'template_kode' => 'REVISI_DIMINTA',
            'status_kirim' => Notifikasi::STATUS_TERKIRIM,
        ]);

        $response = $this->actingAs($this->adminIt)->get(route('internal.adminit.notifikasi-log.index', [
            'search' => '99999',
        ]));

        $response->assertStatus(200);
        $response->assertSee('PBF/DENAH/2026/99999');
        $response->assertSee('PT Farmasi Sejahtera');
    }

    public function test_resend_email_to_pemohon_uses_pemohon_email(): void
    {
        Mail::fake();

        $notifikasi = Notifikasi::create([
            'permohonan_id' => $this->permohonan->id,
            'tujuan_tipe' => Notifikasi::TUJUAN_PEMOHON,
            'tujuan_id' => $this->pbf->id,
            'channel' => Notifikasi::CHANNEL_EMAIL,
            'template_kode' => 'REVISI_DIMINTA',
            'status_kirim' => Notifikasi::STATUS_GAGAL,
            'retry_count' => 0,
        ]);

        $response = $this->actingAs($this->adminIt)->post(route('internal.adminit.notifikasi-log.resend', $notifikasi));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(NotifikasiMail::class, function ($mail) {
            return $mail->hasTo('pbf.sejahtera@test.com');
        });

        $this->assertEquals(Notifikasi::STATUS_TERKIRIM, $notifikasi->fresh()->status_kirim);
        $this->assertEquals(1, $notifikasi->fresh()->retry_count);
    }

    public function test_resend_email_to_staff_uses_staff_email_not_pemohon(): void
    {
        Mail::fake();

        $notifikasi = Notifikasi::create([
            'permohonan_id' => $this->permohonan->id,
            'tujuan_tipe' => Notifikasi::TUJUAN_STAFF,
            'tujuan_id' => $this->staff->id,
            'channel' => Notifikasi::CHANNEL_EMAIL,
            'template_kode' => 'DISTRIBUSI_BARU',
            'status_kirim' => Notifikasi::STATUS_GAGAL,
            'retry_count' => 0,
        ]);

        $response = $this->actingAs($this->adminIt)->post(route('internal.adminit.notifikasi-log.resend', $notifikasi));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify sent to STAFF, NOT to pemohon!
        Mail::assertSent(NotifikasiMail::class, function ($mail) {
            return $mail->hasTo('staff.penguji@bbpom.test');
        });
        Mail::assertNotSent(NotifikasiMail::class, function ($mail) {
            return $mail->hasTo('pbf.sejahtera@test.com');
        });

        $this->assertEquals(Notifikasi::STATUS_TERKIRIM, $notifikasi->fresh()->status_kirim);
        $this->assertEquals(1, $notifikasi->fresh()->retry_count);
    }

    public function test_resend_all_processes_failed_emails(): void
    {
        Mail::fake();

        $notif1 = Notifikasi::create([
            'permohonan_id' => $this->permohonan->id,
            'tujuan_tipe' => Notifikasi::TUJUAN_PEMOHON,
            'tujuan_id' => $this->pbf->id,
            'channel' => Notifikasi::CHANNEL_EMAIL,
            'template_kode' => 'REVISI_DIMINTA',
            'status_kirim' => Notifikasi::STATUS_GAGAL,
            'retry_count' => 0,
        ]);

        $notif2 = Notifikasi::create([
            'permohonan_id' => $this->permohonan->id,
            'tujuan_tipe' => Notifikasi::TUJUAN_STAFF,
            'tujuan_id' => $this->staff->id,
            'channel' => Notifikasi::CHANNEL_EMAIL,
            'template_kode' => 'DISTRIBUSI_BARU',
            'status_kirim' => Notifikasi::STATUS_GAGAL,
            'retry_count' => 0,
        ]);

        $response = $this->actingAs($this->adminIt)->post(route('internal.adminit.notifikasi-log.resend-all'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(NotifikasiMail::class, function ($mail) {
            return $mail->hasTo('pbf.sejahtera@test.com');
        });
        Mail::assertSent(NotifikasiMail::class, function ($mail) {
            return $mail->hasTo('staff.penguji@bbpom.test');
        });

        $this->assertEquals(Notifikasi::STATUS_TERKIRIM, $notif1->fresh()->status_kirim);
        $this->assertEquals(Notifikasi::STATUS_TERKIRIM, $notif2->fresh()->status_kirim);
    }
}
