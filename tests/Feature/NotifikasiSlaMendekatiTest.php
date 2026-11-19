<?php

namespace Tests\Feature;

use App\Models\Distribusi;
use App\Models\Notifikasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\StatusLog;
use App\Models\TemplateNotifikasi;
use App\Models\User;
use App\Services\WhatsappSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NotifikasiSlaMendekatiTest extends TestCase
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

        Carbon::setTestNow('2026-01-14 10:00:00'); // Rabu
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_staff_dapat_wa_berisi_tahap_deadline_dan_sisa_hari(): void
    {
        $staff = $this->staff();
        // Proses evaluasi 7 HK mulai Rabu 7 Jan -> deadline Jumat 16 Jan, sisa 2 (H-2).
        $p = $this->permohonan($staff, Permohonan::STATUS_PROSES_EVALUASI, '2026-01-07 08:00:00');

        $this->artisan('notifikasi:sla-reminder')->assertSuccessful();

        $this->assertCount(1, $this->terkirim);
        [$noWa, $pesan] = $this->terkirim[0];
        $this->assertSame('081200000001', $noWa);
        $this->assertStringContainsString('Yth. Staff Uji', $pesan);
        $this->assertStringContainsString($p->no_registrasi, $pesan);
        $this->assertStringContainsString('Proses Evaluasi', $pesan);
        $this->assertStringContainsString('16 Jan 2026', $pesan);
        $this->assertStringContainsString('Sisa waktu: 2 hari kerja', $pesan);
        $this->assertStringNotContainsString('{{', $pesan);

        $this->assertDatabaseHas('notifikasi', [
            'permohonan_id' => $p->id,
            'tujuan_tipe' => Notifikasi::TUJUAN_STAFF,
            'tujuan_id' => $staff->id,
            'template_kode' => 'SLA_MENDEKATI',
            'status_kirim' => Notifikasi::STATUS_TERKIRIM,
        ]);
    }

    public function test_hanya_h2_dan_h1(): void
    {
        $staff = $this->staff();
        $this->permohonan($staff, Permohonan::STATUS_PROSES_EVALUASI, '2026-01-12 09:00:00'); // sisa 5
        $this->permohonan($staff, Permohonan::STATUS_PROSES_EVALUASI, '2026-01-07 08:00:00'); // sisa 2
        $this->permohonan($staff, Permohonan::STATUS_PROSES_EVALUASI, '2026-01-06 08:00:00'); // sisa 1
        $this->permohonan($staff, Permohonan::STATUS_PROSES_EVALUASI, '2026-01-05 14:00:00'); // sisa 0
        $this->permohonan($staff, Permohonan::STATUS_PROSES_EVALUASI, '2026-01-01 09:00:00'); // telat

        $this->artisan('notifikasi:sla-reminder')->assertSuccessful();

        $this->assertCount(2, $this->terkirim);
    }

    public function test_tidak_kirim_ulang_di_hari_yang_sama(): void
    {
        $this->permohonan($this->staff(), Permohonan::STATUS_PROSES_EVALUASI, '2026-01-06 08:00:00');

        $this->artisan('notifikasi:sla-reminder')->assertSuccessful();
        $this->artisan('notifikasi:sla-reminder')->assertSuccessful();

        $this->assertCount(1, $this->terkirim);
    }

    public function test_tahap_menunggu_surat_juga_diingatkan(): void
    {
        // Menunggu surat 3 HK mulai Selasa 13 Jan -> deadline Jumat 16 Jan, sisa 2.
        $this->permohonan($this->staff(), Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, '2026-01-13 10:00:00');

        $this->artisan('notifikasi:sla-reminder')->assertSuccessful();

        $this->assertCount(1, $this->terkirim);
    }

    public function test_tidak_kirim_jika_template_nonaktif(): void
    {
        TemplateNotifikasi::where('kode_event', 'SLA_MENDEKATI')->update(['is_active' => false]);
        $this->permohonan($this->staff(), Permohonan::STATUS_PROSES_EVALUASI, '2026-01-06 08:00:00');

        $this->artisan('notifikasi:sla-reminder')->assertSuccessful();

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

    private function permohonan(User $staff, string $status, string $mulai): Permohonan
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
            'tanggal_pengajuan' => Carbon::parse($mulai),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);

        StatusLog::create([
            'permohonan_id' => $p->id,
            'status' => $status,
            'waktu_mulai' => Carbon::parse($mulai),
            'is_clock_off' => false,
        ]);

        Distribusi::create([
            'permohonan_id' => $p->id,
            'ketua_tim_id' => $staff->id,
            'staff_id' => $staff->id,
            'jenis' => 'distribusi_awal',
            'is_aktif' => true,
            'tanggal' => Carbon::parse($mulai),
        ]);

        return $p;
    }
}
