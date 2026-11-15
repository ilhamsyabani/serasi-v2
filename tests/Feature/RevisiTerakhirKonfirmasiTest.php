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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Batas revisi = 3 (Permohonan::MAKS_REVISI). Pemberitahuan "revisi terakhir" berupa
 * modal konfirmasi (SweetAlert): ke evaluator saat akan mengirim revisi ke-3, dan ke
 * pemohon saat akan mengunggah berkas revisi ke-3. Notifikasi WA/email tetap yang biasa.
 */
class RevisiTerakhirKonfirmasiTest extends TestCase
{
    use RefreshDatabase;

    /** Pesan WA yang "terkirim": [nomor, isi]. */
    private array $waTerkirim = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\TemplateNotifikasiSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $this->mock(WhatsappSender::class, function ($mock) {
            $mock->shouldReceive('sendWithRetry')->andReturnUsing(function ($no, $isi) {
                $this->waTerkirim[] = [$no, $isi];
                return true;
            });
        });
    }

    private function user(string $kode, string $wa): User
    {
        return User::create([
            'role_id' => Role::where('kode', $kode)->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => ucfirst($kode) . ' Uji',
            'email' => uniqid() . '@bbpom.test',
            'no_whatsapp' => $wa,
            'password' => bcrypt('password'),
            'is_aktif' => true,
        ]);
    }

    private function siapkan(): array
    {
        $katim = $this->user(Role::KETUA_TIM, '081100000001');
        $staff = $this->user(Role::STAFF_SERTIFIKASI, '081100000002');
        $pbf = Pbf::create([
            'nib' => '1234567890123',
            'nama_pbf' => 'PT Uji Farma',
            'email' => 'pt@pbf.test',
            'no_whatsapp' => '081100000003',
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);

        $p = Permohonan::create([
            'no_registrasi' => 'TST/REV4',
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PT Uji Farma',
            'nib_snapshot' => $pbf->nib,
            'email_snapshot' => $pbf->email,
            'no_wa_snapshot' => $pbf->no_whatsapp,
            'status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI,
            'revisi_ke' => 0,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p->id, 'status' => $p->status_saat_ini, 'waktu_mulai' => now(), 'is_clock_off' => false]);

        Disposisi::create(['permohonan_id' => $p->id, 'kepala_balai_id' => $katim->id, 'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now()]);
        Distribusi::create(['permohonan_id' => $p->id, 'ketua_tim_id' => $katim->id, 'staff_id' => $staff->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()]);

        return [$p, $staff, $pbf];
    }

    private function evaluasiTidakLengkap(Permohonan $p, User $staff): void
    {
        $this->actingAs($staff)->put(route('internal.staff.evaluasi.update', $p), [
            'hasil' => 'tidak_lengkap',
            'catatan' => 'Masih belum lengkap',
        ])->assertRedirect(route('internal.staff.dashboard'));
        $p->refresh();
    }

    private function uploadRevisi(Permohonan $p, Pbf $pbf): void
    {
        $pdf = UploadedFile::fake()->createWithContent('revisi.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $this->actingAs($pbf, 'pemohon')
            ->post(route('pemohon.revisi.store', $p), ['dokumen' => [$pdf]])
            ->assertSessionHas('success');
        $p->refresh();
    }

    private function notifTerakhir(Permohonan $p, string $tujuanTipe, string $channel): ?string
    {
        return Notifikasi::where('permohonan_id', $p->id)
            ->where('tujuan_tipe', $tujuanTipe)
            ->where('channel', $channel)
            ->latest('id')
            ->value('template_kode');
    }

    public function test_konfirmasi_swal_hanya_muncul_saat_revisi_terakhir(): void
    {
        [$p, $staff, $pbf] = $this->siapkan();

        for ($ke = 1; $ke <= 2; $ke++) {
            // Evaluator: belum ada konfirmasi revisi terakhir pada evaluasi ke-1..2.
            $this->actingAs($staff)->get(route('internal.staff.evaluasi.edit', $p))
                ->assertOk()
                ->assertDontSee('Kirim Revisi ke-3 (Terakhir)?', false);

            $this->evaluasiTidakLengkap($p, $staff);
            $this->assertSame('revisi_' . $ke, $p->status_saat_ini);

            // Pemohon revisi 1-2: tanpa konfirmasi revisi terakhir.
            $this->actingAs($pbf, 'pemohon')->get(route('pemohon.revisi.show', $p))
                ->assertOk()
                ->assertDontSee('Revisi Terakhir (ke-', false);

            $this->uploadRevisi($p, $pbf);
        }

        // Evaluasi ke-3 (setelah berkas revisi 2 masuk): evaluator dikonfirmasi via Swal.
        $this->actingAs($staff)->get(route('internal.staff.evaluasi.edit', $p))
            ->assertOk()
            ->assertSee('Kirim Revisi ke-3 (Terakhir)?', false);

        // Evaluasi gagal ke-3 -> revisi_3 (terakhir).
        $this->evaluasiTidakLengkap($p, $staff);
        $this->assertSame(Permohonan::STATUS_REVISI_3, $p->status_saat_ini);
        $this->assertTrue($p->isRevisiTerakhir());

        // Pemohon: konfirmasi Swal sebelum mengirim berkas revisi terakhir.
        $this->actingAs($pbf, 'pemohon')->get(route('pemohon.revisi.show', $p))
            ->assertOk()
            ->assertSee('Revisi Terakhir (ke-3 dari 3)', false);

        // Pemberitahuan cukup lewat modal: notifikasi WA/email tetap yang biasa.
        $this->assertSame('REVISI_DIMINTA', $this->notifTerakhir($p, Notifikasi::TUJUAN_PEMOHON, Notifikasi::CHANNEL_WHATSAPP));
        $this->uploadRevisi($p, $pbf);
        $this->assertSame('REVISI_DITERIMA', $this->notifTerakhir($p, Notifikasi::TUJUAN_STAFF, Notifikasi::CHANNEL_WHATSAPP));
        $this->assertSame(0, Notifikasi::where('template_kode', 'like', 'REVISI_TERAKHIR%')->count());

        // Evaluasi gagal ke-4 -> ditutup; pesan penutupan menyebut 3 kali revisi.
        $this->waTerkirim = [];
        $this->evaluasiTidakLengkap($p, $staff);
        $this->assertSame(Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG, $p->status_saat_ini);
        $waTutup = collect($this->waTerkirim)->firstWhere(0, '081100000003');
        $this->assertNotNull($waTutup);
        $this->assertStringContainsString('Setelah 3 kali revisi', $waTutup[1]);
    }
}
