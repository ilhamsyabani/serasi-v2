<?php

namespace Tests\Feature;

use App\Models\Distribusi;
use App\Models\Notifikasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\SlaConfig;
use App\Models\StatusLog;
use App\Models\User;
use App\Services\WhatsappSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotifikasiSlaTerlambatTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int,array{0:string,1:string}> */
    private array $terkirim = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\SlaConfigSeeder::class);

        $this->app->instance(WhatsappSender::class, new class($this->terkirim) extends WhatsappSender {
            public function __construct(private array &$log) {}
            public function send(string $noWa, string $pesan): bool { $this->log[] = [$noWa, $pesan]; return true; }
            public function sendWithRetry(string $noWa, string $pesan, int $maxRetries = 3): bool { return $this->send($noWa, $pesan); }
        });
    }

    public function test_staff_dapat_wa_saat_tahap_evaluasi_melewati_sla(): void
    {
        $staff = $this->staff();
        $p = $this->permohonanDenganTahap($staff, Permohonan::STATUS_PROSES_EVALUASI, Carbon::parse('2026-01-01 09:00'));

        $this->artisan('notifikasi:sla-terlambat')->assertSuccessful();

        $this->assertCount(1, $this->terkirim);
        [$noWa, $pesan] = $this->terkirim[0];
        $this->assertSame('081200000001', $noWa);
        $this->assertStringContainsString($p->no_registrasi, $pesan);
        $this->assertStringContainsString('Proses Evaluasi', $pesan);
        $this->assertStringContainsString('Yth. Staff Uji', $pesan);
        $this->assertStringNotContainsString('{{', $pesan);

        $this->assertDatabaseHas('notifikasi', [
            'permohonan_id' => $p->id,
            'tujuan_tipe' => Notifikasi::TUJUAN_STAFF,
            'tujuan_id' => $staff->id,
            'template_kode' => 'SLA_TERLAMBAT',
            'status_kirim' => Notifikasi::STATUS_TERKIRIM,
        ]);
    }

    public function test_tidak_kirim_ulang_di_hari_yang_sama(): void
    {
        $this->permohonanDenganTahap($this->staff(), Permohonan::STATUS_PROSES_EVALUASI, Carbon::parse('2026-01-01 09:00'));

        $this->artisan('notifikasi:sla-terlambat')->assertSuccessful();
        $this->artisan('notifikasi:sla-terlambat')->assertSuccessful();

        $this->assertCount(1, $this->terkirim);
    }

    public function test_tidak_kirim_jika_masih_dalam_batas_sla(): void
    {
        $this->permohonanDenganTahap($this->staff(), Permohonan::STATUS_PROSES_EVALUASI, now());

        $this->artisan('notifikasi:sla-terlambat')->assertSuccessful();

        $this->assertCount(0, $this->terkirim);
    }

    public function test_tahap_revisi_clock_off_tidak_dianggap_terlambat(): void
    {
        $this->permohonanDenganTahap($this->staff(), Permohonan::STATUS_REVISI_1, Carbon::parse('2026-01-01 09:00'), clockOff: true);

        $this->artisan('notifikasi:sla-terlambat')->assertSuccessful();

        $this->assertCount(0, $this->terkirim);
    }

    public function test_tidak_kirim_jika_template_nonaktif(): void
    {
        \App\Models\TemplateNotifikasi::where('kode_event', 'SLA_TERLAMBAT')->update(['is_active' => false]);
        $this->permohonanDenganTahap($this->staff(), Permohonan::STATUS_PROSES_EVALUASI, Carbon::parse('2026-01-01 09:00'));

        $this->artisan('notifikasi:sla-terlambat')->assertSuccessful();

        $this->assertCount(0, $this->terkirim);
    }

    private function staff(): User
    {
        return User::create([
            'role_id' => Role::where('kode', 'staff_sertifikasi')->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => 'Staff Uji',
            'email' => uniqid() . '@bbpom.test',
            'no_whatsapp' => '081200000001',
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);
    }

    private function permohonanDenganTahap(User $staff, string $status, Carbon $mulai, bool $clockOff = false): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '08001',
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => false,
        ]);

        $p = Permohonan::create([
            'no_registrasi' => 'SLA/' . uniqid(),
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PBF Uji',
            'nib_snapshot' => '123',
            'email_snapshot' => 'uji@pbf.test',
            'no_wa_snapshot' => '0800',
            'status_saat_ini' => $status,
            'tanggal_pengajuan' => $mulai,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);

        StatusLog::create([
            'permohonan_id' => $p->id,
            'status' => $status,
            'waktu_mulai' => $mulai,
            'is_clock_off' => $clockOff,
        ]);

        Distribusi::create([
            'permohonan_id' => $p->id,
            'ketua_tim_id' => $staff->id,
            'staff_id' => $staff->id,
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => $mulai,
        ]);

        return $p;
    }
}
