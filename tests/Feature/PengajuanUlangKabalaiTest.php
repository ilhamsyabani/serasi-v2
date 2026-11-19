<?php

namespace Tests\Feature;

use App\Models\DokumenPermohonan;
use App\Models\Evaluasi;
use App\Models\Notifikasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\StatusLog;
use App\Models\User;
use App\Services\WhatsappSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kepala Balai mengajukan ulang permohonan yang ditutup (CLAUDE.md §3 poin 6 & 7).
 */
class PengajuanUlangKabalaiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int,array{0:string,1:string}> */
    private array $wa = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $this->app->instance(WhatsappSender::class, new class($this->wa) extends WhatsappSender {
            public function __construct(private array &$log) {}
            public function send(string $noWa, string $pesan): bool { $this->log[] = [$noWa, $pesan]; return true; }
            public function sendWithRetry(string $noWa, string $pesan, int $maxRetries = 3): bool { return $this->send($noWa, $pesan); }
        });
    }

    public function test_daftar_dan_form_menampilkan_permohonan_ditutup_dan_data_pbf(): void
    {
        $kabalai = $this->user('kepala_balai');
        $lama = $this->permohonanDitutup();

        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.index'))
            ->assertOk()
            ->assertSee($lama->no_registrasi)
            ->assertSee('Berkas denah kurang jelas')
            ->assertSee(route('internal.kabalai.pengajuan-ulang.create', $lama));

        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.create', $lama))
            ->assertOk()
            ->assertSee($lama->pbf->email)
            ->assertSee($lama->pbf->no_whatsapp)
            ->assertSee('Berkas denah kurang jelas')
            ->assertDontSee('name="email"', false);
    }

    public function test_ajukan_ulang_membuat_permohonan_baru_tertaut_dengan_akun_sama(): void
    {
        $kabalai = $this->user('kepala_balai');
        $lama = $this->permohonanDitutup();

        $this->actingAs($kabalai)
            ->post(route('internal.kabalai.pengajuan-ulang.store', $lama), [
                'rancangan_denah' => $this->pdf(),
                'surat_permohonan' => $this->pdf(),
            ])
            ->assertSessionHas('success');

        $baru = Permohonan::where('parent_permohonan_id', $lama->id)->firstOrFail();
        $this->assertNotSame($lama->no_registrasi, $baru->no_registrasi);
        $this->assertSame($lama->pbf_id, $baru->pbf_id);
        $this->assertSame(Permohonan::STATUS_PENGAJUAN, $baru->status_saat_ini);
        $this->assertSame(0, (int) $baru->revisi_ke);
        $this->assertSame($kabalai->id, $baru->kepala_balai_id);
        $this->assertSame(Permohonan::DIBUAT_OLEH_KEPALA_BALAI, $baru->dibuat_oleh_tipe);
        $this->assertEqualsCanonicalizing(
            ['rancangan_denah', 'surat_permohonan'],
            DokumenPermohonan::where('permohonan_id', $baru->id)->pluck('jenis_dokumen')->all()
        );
        $this->assertDatabaseHas('audit_trail', ['permohonan_id' => $baru->id, 'aksi' => 'pengajuan_ulang']);

        // Permohonan lama tetap ditutup (riwayat tidak diubah).
        $this->assertSame(Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG, $lama->refresh()->status_saat_ini);

        // Pemohon dikabari tanpa kredensial baru.
        $this->assertCount(1, $this->wa);
        $this->assertStringContainsString($baru->no_registrasi, $this->wa[0][1]);
        $this->assertStringContainsString($lama->no_registrasi, $this->wa[0][1]);
        $this->assertSame(0, Notifikasi::where('template_kode', 'AKUN_BARU')->count());
        $this->assertSame(2, Notifikasi::where('template_kode', 'PENGAJUAN_ULANG')->count());

        // Baru tampil di daftar disposisi Kepala Balai pengajunya.
        $this->actingAs($kabalai)->get(route('internal.kabalai.disposisi.index'))->assertSee($baru->no_registrasi);
    }

    public function test_hanya_bisa_diajukan_ulang_sekali_dan_tombol_pemohon_hilang(): void
    {
        $kabalai = $this->user('kepala_balai');
        $lama = $this->permohonanDitutup();

        $this->actingAs($kabalai)->post(route('internal.kabalai.pengajuan-ulang.store', $lama), ['rancangan_denah' => $this->pdf()]);

        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.create', $lama))->assertForbidden();
        $this->actingAs($kabalai)->post(route('internal.kabalai.pengajuan-ulang.store', $lama), ['rancangan_denah' => $this->pdf()])->assertForbidden();
        $this->assertSame(1, Permohonan::where('parent_permohonan_id', $lama->id)->count());

        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.index'))->assertDontSee($lama->no_registrasi);
        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.index', ['tab' => 'sudah']))->assertSee($lama->no_registrasi);

        // Pemohon tidak bisa lagi mengajukan ulang mandiri.
        $this->actingAs($lama->pbf, 'pemohon')->get(route('pemohon.permohonan.create'))->assertForbidden();
    }

    public function test_tidak_bisa_bila_pemohon_sudah_mengajukan_ulang_mandiri(): void
    {
        $kabalai = $this->user('kepala_balai');
        $lama = $this->permohonanDitutup();
        Permohonan::create([
            'no_registrasi' => 'TST/' . uniqid(), 'pbf_id' => $lama->pbf_id, 'parent_permohonan_id' => $lama->id,
            'nama_pbf_snapshot' => 'PBF Uji', 'nib_snapshot' => '1', 'email_snapshot' => 'a@b.c', 'no_wa_snapshot' => '1',
            'status_saat_ini' => Permohonan::STATUS_PENGAJUAN, 'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_PEMOHON,
        ]);

        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.create', $lama))->assertForbidden();
        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.index'))->assertDontSee($lama->no_registrasi);
    }

    public function test_wajib_minimal_satu_dokumen(): void
    {
        $kabalai = $this->user('kepala_balai');
        $lama = $this->permohonanDitutup();

        $this->actingAs($kabalai)->post(route('internal.kabalai.pengajuan-ulang.store', $lama), [])
            ->assertSessionHasErrors('dokumen');

        $this->assertSame(0, Permohonan::where('parent_permohonan_id', $lama->id)->count());
    }

    public function test_hanya_kepala_balai_dan_hanya_permohonan_ditutup(): void
    {
        $katim = $this->user('ketua_tim');
        $kabalai = $this->user('kepala_balai');
        $lama = $this->permohonanDitutup();

        $this->actingAs($katim)->get(route('internal.kabalai.pengajuan-ulang.index'))->assertForbidden();
        $this->actingAs($katim)->post(route('internal.kabalai.pengajuan-ulang.store', $lama), ['rancangan_denah' => $this->pdf()])->assertForbidden();

        $lama->update(['status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI]);
        $this->actingAs($kabalai)->get(route('internal.kabalai.pengajuan-ulang.create', $lama))->assertForbidden();
    }

    public function test_pemohon_melihat_relasi_lama_dan_baru(): void
    {
        $kabalai = $this->user('kepala_balai');
        $lama = $this->permohonanDitutup();
        $this->actingAs($kabalai)->post(route('internal.kabalai.pengajuan-ulang.store', $lama), ['rancangan_denah' => $this->pdf()]);
        $baru = Permohonan::where('parent_permohonan_id', $lama->id)->firstOrFail();

        $this->actingAs($lama->pbf, 'pemohon')->get(route('pemohon.permohonan.show', $baru))
            ->assertOk()
            ->assertSee(route('pemohon.permohonan.show', $lama))
            ->assertSee('diajukan oleh BBPOM');

        $this->actingAs($lama->pbf, 'pemohon')->get(route('pemohon.permohonan.show', $lama))
            ->assertOk()
            ->assertSee('Sudah diajukan ulang')
            ->assertSee(route('pemohon.permohonan.show', $baru));
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

    private function permohonanDitutup(): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PT. Uji Cabang Malang',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '0812' . random_int(1000000, 9999999),
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);

        $p = Permohonan::create([
            'no_registrasi' => 'TST/' . uniqid(),
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => $pbf->nama_pbf,
            'nib_snapshot' => $pbf->nib,
            'email_snapshot' => $pbf->email,
            'no_wa_snapshot' => $pbf->no_whatsapp,
            'status_saat_ini' => Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG,
            'revisi_ke' => Permohonan::MAKS_REVISI,
            'tanggal_pengajuan' => now()->subMonth(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);

        StatusLog::create(['permohonan_id' => $p->id, 'status' => Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG, 'waktu_mulai' => now(), 'is_clock_off' => false]);
        $staff = $this->user('staff_sertifikasi');
        Evaluasi::create(['permohonan_id' => $p->id, 'staff_id' => $staff->id, 'siklus_ke' => 4, 'hasil' => 'tidak_lengkap', 'catatan' => 'Berkas denah kurang jelas', 'tanggal_evaluasi' => now()]);

        return $p;
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('dok.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
    }
}
