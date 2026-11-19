<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\Notifikasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\StatusLog;
use App\Models\User;
use App\Services\WhatsappSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ketua Tim boleh menugaskan dirinya sendiri sebagai evaluator (CLAUDE.md §3 poin 1),
 * lalu mengevaluasi lewat form yang sama dengan staff. Tetap 1 permohonan = 1 evaluator.
 */
class KetuaTimEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int,array{0:string,1:string}> */
    private array $wa = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->app->instance(WhatsappSender::class, new class($this->wa) extends WhatsappSender {
            public function __construct(private array &$log) {}
            public function send(string $noWa, string $pesan): bool { $this->log[] = [$noWa, $pesan]; return true; }
            public function sendWithRetry(string $noWa, string $pesan, int $maxRetries = 3): bool { return $this->send($noWa, $pesan); }
        });
    }

    public function test_katim_bisa_distribusi_ke_diri_sendiri_lalu_evaluasi(): void
    {
        $katim = $this->user('ketua_tim');
        $p = $this->permohonanDidisposisikan($katim);

        $this->actingAs($katim)->get(route('internal.ketua_tim.distribusi.index'))
            ->assertOk()->assertSee('(Saya — Ketua Tim)');

        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $katim->id])
            ->assertRedirect(route('internal.staff.evaluasi.edit', $p));

        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->refresh()->status_saat_ini);
        $this->assertSame($katim->id, $p->distribusiAktif->staff_id);
        // Tidak ada notifikasi ke dirinya sendiri.
        $this->assertSame(0, Notifikasi::where('tujuan_id', $katim->id)->count());

        $this->actingAs($katim)->get(route('internal.staff.permohonan.index'))
            ->assertOk()->assertSee($p->no_registrasi);
        $this->actingAs($katim)->get(route('internal.ketua_tim.dashboard'))
            ->assertOk()->assertSee(route('internal.staff.evaluasi.edit', $p));
        $this->actingAs($katim)->get(route('internal.staff.evaluasi.edit', $p))->assertOk();

        $this->actingAs($katim)
            ->put(route('internal.staff.evaluasi.update', $p), ['hasil' => 'lengkap'])
            ->assertRedirect(route('internal.staff.permohonan.index'));

        $this->assertSame(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, $p->refresh()->status_saat_ini);
        $this->assertSame(0, Notifikasi::where('tujuan_id', $katim->id)->count());
    }

    public function test_katim_tidak_bisa_evaluasi_permohonan_yang_dipegang_staff(): void
    {
        $katim = $this->user('ketua_tim');
        $staff = $this->user('staff_sertifikasi');
        $p = $this->permohonanDidisposisikan($katim);

        $this->actingAs($katim)->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $staff->id]);

        $this->actingAs($katim)->get(route('internal.staff.evaluasi.edit', $p))->assertForbidden();
        $this->actingAs($katim)->put(route('internal.staff.evaluasi.update', $p), ['hasil' => 'lengkap'])->assertForbidden();
        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->refresh()->status_saat_ini);
    }

    public function test_katim_lain_tidak_bisa_ditugaskan(): void
    {
        $katim = $this->user('ketua_tim');
        $katimLain = $this->user('ketua_tim');
        $p = $this->permohonanDidisposisikan($katim);

        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $katimLain->id])
            ->assertSessionHasErrors('staff_id');

        $this->assertSame(Permohonan::STATUS_DIDISPOSISIKAN, $p->refresh()->status_saat_ini);
    }

    public function test_katim_bisa_ambil_alih_lewat_alihkan_staff(): void
    {
        $katim = $this->user('ketua_tim');
        $staff = $this->user('staff_sertifikasi');
        $p = $this->permohonanDidisposisikan($katim);
        $this->actingAs($katim)->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $staff->id]);

        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.eskalasi.reassign', $p), ['staff_id' => $katim->id, 'alasan' => 'Staff sedang cuti panjang'])
            ->assertSessionHas('success');

        $this->assertSame($katim->id, $p->refresh()->distribusiAktif->staff_id);
        $this->assertSame(1, Distribusi::where('permohonan_id', $p->id)->where('is_aktif', true)->count());
        $this->actingAs($katim)->get(route('internal.staff.evaluasi.edit', $p))->assertOk();
        // Staff lama dikabari; Ketua Tim tidak mengirim WA ke dirinya sendiri.
        $this->assertSame(1, Notifikasi::where('template_kode', 'REASSIGNMENT')->where('tujuan_id', $staff->id)->count());
        $this->assertSame(0, Notifikasi::where('template_kode', 'REASSIGNMENT')->where('tujuan_id', $katim->id)->count());
    }

    public function test_staff_tetap_tidak_bisa_buka_dashboard_atau_distribusi_katim(): void
    {
        $staff = $this->user('staff_sertifikasi');
        $katim = $this->user('ketua_tim');

        $this->actingAs($staff)->get(route('internal.ketua_tim.distribusi.index'))->assertForbidden();
        $this->actingAs($katim)->get(route('internal.staff.dashboard'))->assertForbidden();
    }

    private function user(string $kode): User
    {
        return User::create([
            'role_id' => Role::where('kode', $kode)->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => ucfirst($kode) . ' ' . uniqid(),
            'email' => uniqid() . '@bbpom.test',
            'no_whatsapp' => '0812' . random_int(1000000, 9999999),
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);
    }

    private function permohonanDidisposisikan(User $katim): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '0812' . random_int(1000000, 9999999),
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);

        $p = Permohonan::create([
            'no_registrasi' => 'TST/' . uniqid(),
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PBF Uji',
            'nib_snapshot' => $pbf->nib,
            'email_snapshot' => $pbf->email,
            'no_wa_snapshot' => $pbf->no_whatsapp,
            'status_saat_ini' => Permohonan::STATUS_DIDISPOSISIKAN,
            'revisi_ke' => 0,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);

        StatusLog::create(['permohonan_id' => $p->id, 'status' => Permohonan::STATUS_DIDISPOSISIKAN, 'waktu_mulai' => now(), 'is_clock_off' => false]);
        Disposisi::create(['permohonan_id' => $p->id, 'kepala_balai_id' => $katim->id, 'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now()]);

        return $p;
    }
}
