<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\Evaluasi;
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
 * Admin IT mengembalikan permohonan ke revisi BERIKUTNYA (koreksi status).
 * Kuota MAKS_REVISI tetap terjaga: setelah kuota habis, permohonan ditutup.
 */
class KoreksiStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $katim;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\TemplateNotifikasiSeeder::class);
        $this->mock(WhatsappSender::class, fn ($m) => $m->shouldReceive('sendWithRetry')->andReturnTrue());

        $this->admin = $this->user(Role::ADMIN_IT, '081100000009');
        $this->katim = $this->user(Role::KETUA_TIM, '081100000001');
        $this->staff = $this->user(Role::STAFF_SERTIFIKASI, '081100000002');
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

    /**
     * Permohonan yang sudah melewati $jumlahRevisi siklus revisi dan kini berstatus $status.
     * Bila menunggu surat, evaluasi terakhirnya "Lengkap".
     */
    private function permohonan(int $jumlahRevisi, string $status): Permohonan
    {
        $pbf = Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PT Uji Farma',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '081100000003',
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);
        $p = Permohonan::create([
            'no_registrasi' => 'TST/' . uniqid(),
            'pbf_id' => $pbf->id,
            'nama_pbf_snapshot' => 'PT Uji Farma',
            'nib_snapshot' => $pbf->nib,
            'email_snapshot' => $pbf->email,
            'no_wa_snapshot' => $pbf->no_whatsapp,
            'status_saat_ini' => $status,
            'revisi_ke' => $jumlahRevisi,
            'tanggal_pengajuan' => now(),
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);

        $waktu = now()->subDays(30);
        for ($i = 1; $i <= $jumlahRevisi; $i++) {
            Evaluasi::create(['permohonan_id' => $p->id, 'staff_id' => $this->staff->id, 'siklus_ke' => $i, 'hasil' => 'tidak_lengkap', 'catatan' => "Kurang $i", 'tanggal_evaluasi' => $waktu]);
            StatusLog::create(['permohonan_id' => $p->id, 'status' => "revisi_$i", 'waktu_mulai' => $waktu, 'waktu_selesai' => $waktu->copy()->addDay(), 'is_clock_off' => true]);
            $waktu = $waktu->copy()->addDays(2);
        }
        if ($status === Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN) {
            Evaluasi::create(['permohonan_id' => $p->id, 'staff_id' => $this->staff->id, 'siklus_ke' => $jumlahRevisi + 1, 'hasil' => 'lengkap', 'catatan' => 'Sesuai', 'tanggal_evaluasi' => now()->subDay()]);
        }
        StatusLog::create(['permohonan_id' => $p->id, 'status' => $status, 'waktu_mulai' => now()->subDay(), 'is_clock_off' => false]);

        Disposisi::create(['permohonan_id' => $p->id, 'kepala_balai_id' => $this->katim->id, 'ketua_tim_id' => $this->katim->id, 'tanggal_disposisi' => now()]);
        Distribusi::create(['permohonan_id' => $p->id, 'ketua_tim_id' => $this->katim->id, 'staff_id' => $this->staff->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()]);

        return $p;
    }

    private function kembalikan(Permohonan $p, array $extra = [])
    {
        return $this->actingAs($this->admin)->post(route('internal.adminit.koreksi-status.store', $p), $extra + [
            'catatan' => 'Pintu utama belum digambar',
            'alasan' => 'Evaluator keliru memilih Lengkap',
            'kirim_notifikasi' => '1',
        ]);
    }

    public function test_menunggu_surat_setelah_revisi_1_kembali_ke_revisi_2(): void
    {
        $p = $this->permohonan(1, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);
        $evaluasiKeliru = $p->evaluasiTerakhir;

        $this->kembalikan($p)->assertRedirect(route('internal.adminit.koreksi-status.index'));
        $p->refresh();

        $this->assertSame('revisi_2', $p->status_saat_ini);
        $this->assertSame(2, (int) $p->revisi_ke);

        // Riwayat lama tidak ditimpa; evaluasi koreksi = siklus yang sama, jadi catatan terbaru.
        $this->assertDatabaseHas('evaluasi', ['id' => $evaluasiKeliru->id, 'hasil' => 'lengkap']);
        $this->assertSame('tidak_lengkap', $p->evaluasiTerakhir->hasil);
        $this->assertSame('Pintu utama belum digambar', $p->evaluasiTerakhir->catatan);
        $this->assertSame($evaluasiKeliru->siklus_ke, $p->evaluasiTerakhir->siklus_ke);

        // status_log: menunggu ditutup, revisi_2 baru terbuka & clock-off.
        $this->assertSame(1, StatusLog::where('permohonan_id', $p->id)->whereNull('waktu_selesai')->count());
        $this->assertTrue((bool) StatusLog::where('permohonan_id', $p->id)->whereNull('waktu_selesai')->value('is_clock_off'));

        $this->assertDatabaseHas('audit_trail', ['permohonan_id' => $p->id, 'aksi' => 'koreksi_status', 'user_id' => $this->admin->id]);

        // Notifikasi: pemohon (WA+email), ketua tim (WA+email), staff evaluator (WA).
        $this->assertSame(2, Notifikasi::where('permohonan_id', $p->id)->where('tujuan_tipe', Notifikasi::TUJUAN_PEMOHON)->where('template_kode', 'REVISI_DIMINTA')->count());
        $this->assertSame(2, Notifikasi::where('permohonan_id', $p->id)->where('tujuan_tipe', Notifikasi::TUJUAN_KETUA_TIM)->count());
        $this->assertSame(1, Notifikasi::where('permohonan_id', $p->id)->where('tujuan_tipe', Notifikasi::TUJUAN_STAFF)->count());
    }

    public function test_siklus_evaluasi_berikutnya_tetap_berurutan_setelah_koreksi(): void
    {
        $p = $this->permohonan(1, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);
        $this->kembalikan($p);

        // Simulasikan pemohon sudah unggah revisi ke-2, lalu evaluator menilai Tidak Lengkap → revisi_3.
        app(\App\Services\StatusTransitionService::class)->transisi($p->refresh(), Permohonan::STATUS_PROSES_EVALUASI, null, null, 'pemohon');
        $this->actingAs($this->staff)->put(route('internal.staff.evaluasi.update', $p), ['hasil' => 'tidak_lengkap', 'catatan' => 'Masih kurang']);

        $this->assertSame('revisi_3', $p->refresh()->status_saat_ini);
    }

    public function test_kuota_habis_maka_ditutup(): void
    {
        $p = $this->permohonan(Permohonan::MAKS_REVISI, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);

        $this->kembalikan($p);

        $this->assertSame(Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG, $p->refresh()->status_saat_ini);
        $this->assertSame(0, StatusLog::where('permohonan_id', $p->id)->where('status', 'revisi_4')->count());
        $this->assertDatabaseHas('notifikasi', ['permohonan_id' => $p->id, 'template_kode' => 'DITUTUP_PENGAJUAN_ULANG']);
    }

    public function test_dari_proses_evaluasi_ke_revisi_berikutnya(): void
    {
        $p = $this->permohonan(2, Permohonan::STATUS_PROSES_EVALUASI);

        $this->kembalikan($p);

        $p->refresh();
        $this->assertSame('revisi_3', $p->status_saat_ini);
        $this->assertSame(3, $p->evaluasiTerakhir->siklus_ke);
    }

    public function test_status_lain_ditolak(): void
    {
        foreach (['revisi_1', Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN, Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG] as $status) {
            $p = $this->permohonan(1, $status);
            $this->kembalikan($p)->assertForbidden();
            $this->assertSame($status, $p->refresh()->status_saat_ini);
        }
    }

    public function test_status_tidak_sinkron_dengan_status_log_ditolak(): void
    {
        $p = $this->permohonan(1, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);
        StatusLog::where('permohonan_id', $p->id)->whereNull('waktu_selesai')->update(['status' => 'proses_evaluasi']);

        $this->kembalikan($p)->assertForbidden();
        $this->assertSame(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, $p->refresh()->status_saat_ini);
    }

    public function test_hanya_admin_it(): void
    {
        $p = $this->permohonan(1, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);

        $this->actingAs($this->katim)->get(route('internal.adminit.koreksi-status.index'))->assertForbidden();
        $this->actingAs($this->katim)->post(route('internal.adminit.koreksi-status.store', $p), ['catatan' => 'x', 'alasan' => 'x'])->assertForbidden();
        $this->assertSame(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, $p->refresh()->status_saat_ini);
    }

    public function test_catatan_dan_alasan_wajib(): void
    {
        $p = $this->permohonan(1, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);

        $this->kembalikan($p, ['catatan' => '', 'alasan' => ''])->assertSessionHasErrors(['catatan', 'alasan']);
        $this->assertSame(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, $p->refresh()->status_saat_ini);
    }

    public function test_tanpa_notifikasi(): void
    {
        $p = $this->permohonan(1, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);

        $this->kembalikan($p, ['kirim_notifikasi' => '0']);

        $this->assertSame('revisi_2', $p->refresh()->status_saat_ini);
        $this->assertSame(0, Notifikasi::where('permohonan_id', $p->id)->count());
    }

    public function test_halaman_index_menampilkan_tujuan(): void
    {
        $p = $this->permohonan(1, Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);

        $this->actingAs($this->admin)->get(route('internal.adminit.koreksi-status.index'))
            ->assertOk()
            ->assertSee($p->no_registrasi)
            ->assertSee('Revisi ke-2');
    }
}
