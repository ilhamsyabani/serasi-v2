<?php

namespace Tests\Feature;

use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\Evaluasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\Role;
use App\Models\StatusLog;
use App\Models\SuratPengesahan;
use App\Models\User;
use App\Services\StatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Penjaga alur kerja: setiap aksi hanya boleh dilakukan oleh aktor yang berhak
 * dan hanya pada status yang tepat (CLAUDE.md §3 & §5).
 */
class WorkflowGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function user(string $kode, bool $aktif = true): User
    {
        return User::create([
            'role_id' => Role::where('kode', $kode)->value('id'),
            'nip' => (string) random_int(10000000, 99999999),
            'nama' => ucfirst($kode) . ' ' . uniqid(),
            'email' => uniqid() . '@bbpom.test',
            'password' => bcrypt('password'),
            'is_aktif' => $aktif,
        ]);
    }

    private function pbf(): Pbf
    {
        return Pbf::create([
            'nib' => (string) random_int(1000000000000, 9999999999999),
            'nama_pbf' => 'PBF Uji',
            'email' => uniqid() . '@pbf.test',
            'no_whatsapp' => '0812' . random_int(1000000, 9999999),
            'password_hash' => bcrypt('x'),
            'otp_terverifikasi' => true,
        ]);
    }

    private function permohonan(string $status, array $attr = []): Permohonan
    {
        $pbf = $attr['pbf'] ?? $this->pbf();
        unset($attr['pbf']);

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

    private function tugaskan(Permohonan $p, User $katim, User $staff): void
    {
        Disposisi::create(['permohonan_id' => $p->id, 'kepala_balai_id' => $katim->id, 'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now()]);
        Distribusi::create(['permohonan_id' => $p->id, 'ketua_tim_id' => $katim->id, 'staff_id' => $staff->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('surat.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
    }

    // ── Surat Pengesahan ──────────────────────────────

    public function test_surat_hanya_bisa_diterbitkan_staff_penanggung_jawab(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $staffLain = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);
        $this->tugaskan($p, $katim, $staff);

        $this->actingAs($staffLain)->get(route('internal.staff.surat.edit', $p))->assertForbidden();
        $this->actingAs($staffLain)
            ->put(route('internal.staff.surat.update', $p), ['file_surat' => $this->pdf(), 'nomor_surat' => 'X/1'])
            ->assertForbidden();

        $this->assertSame(0, SuratPengesahan::count());
        $this->assertSame(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, $p->refresh()->status_saat_ini);
    }

    public function test_surat_tidak_bisa_diterbitkan_di_luar_tahap_menunggu_surat(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_PROSES_EVALUASI);
        $this->tugaskan($p, $katim, $staff);

        $this->actingAs($staff)
            ->put(route('internal.staff.surat.update', $p), ['file_surat' => $this->pdf(), 'nomor_surat' => 'X/1'])
            ->assertRedirect(route('internal.staff.dashboard'))
            ->assertSessionHas('error');

        $this->assertSame(0, SuratPengesahan::count());
        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->refresh()->status_saat_ini);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_surat_terbit_sekali_dan_submit_kedua_ditolak(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN);
        $this->tugaskan($p, $katim, $staff);

        $this->actingAs($staff)
            ->put(route('internal.staff.surat.update', $p), ['file_surat' => $this->pdf(), 'nomor_surat' => 'X/1'])
            ->assertSessionHas('success');

        $this->assertSame(Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN, $p->refresh()->status_saat_ini);
        $this->assertSame(1, SuratPengesahan::count());

        $this->actingAs($staff)
            ->put(route('internal.staff.surat.update', $p), ['file_surat' => $this->pdf(), 'nomor_surat' => 'X/2'])
            ->assertSessionHas('error');

        $this->assertSame(1, SuratPengesahan::count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
        // Tidak ada berkas yang mendarat di disk public (terbuka tanpa login).
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    // ── Evaluasi ──────────────────────────────────────

    public function test_evaluasi_tidak_lengkap_wajib_ada_catatan(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_PROSES_EVALUASI);
        $this->tugaskan($p, $katim, $staff);

        $this->actingAs($staff)
            ->put(route('internal.staff.evaluasi.update', $p), ['hasil' => 'tidak_lengkap', 'catatan' => ''])
            ->assertSessionHasErrors('catatan');

        $this->assertSame(0, Evaluasi::count());
        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->refresh()->status_saat_ini);

        // Lengkap tetap cukup klik OK, tanpa catatan (CLAUDE.md §3 poin 12).
        $this->actingAs($staff)
            ->put(route('internal.staff.evaluasi.update', $p), ['hasil' => 'lengkap'])
            ->assertSessionHasNoErrors();

        $this->assertSame(Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN, $p->refresh()->status_saat_ini);
    }

    // ── Status akhir ──────────────────────────────────

    public function test_permohonan_berstatus_akhir_tidak_bisa_ditransisikan_lagi(): void
    {
        $p = $this->permohonan(Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN);

        $this->expectException(\RuntimeException::class);
        (new StatusTransitionService())->transisi($p, Permohonan::STATUS_PROSES_EVALUASI);
    }

    // ── Distribusi ────────────────────────────────────

    public function test_distribusi_hanya_dari_status_didisposisikan_oleh_ketua_tim_pemilik(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $katimLain = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);

        $p = $this->permohonan(Permohonan::STATUS_DIDISPOSISIKAN);
        Disposisi::create(['permohonan_id' => $p->id, 'kepala_balai_id' => $katim->id, 'ketua_tim_id' => $katim->id, 'tanggal_disposisi' => now()]);

        // Ketua Tim lain tidak boleh.
        $this->actingAs($katimLain)
            ->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $staff->id])
            ->assertForbidden();

        // Tujuan harus Staff Sertifikasi aktif, bukan sembarang user.
        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $katimLain->id])
            ->assertSessionHasErrors('staff_id');
        $this->assertSame(0, Distribusi::count());

        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $staff->id])
            ->assertSessionHas('success');
        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->refresh()->status_saat_ini);

        // Kiriman ulang ditolak — tetap 1 staff aktif per permohonan.
        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.distribusi.store', $p), ['staff_id' => $staff->id])
            ->assertSessionHas('error');
        $this->assertSame(1, Distribusi::where('permohonan_id', $p->id)->where('is_aktif', true)->count());
    }

    // ── Disposisi & pengajuan ulang ───────────────────

    public function test_pengajuan_ulang_pemohon_muncul_dan_bisa_didisposisikan_kepala_balai(): void
    {
        $kabalai = $this->user(Role::KEPALA_BALAI);
        $katim = $this->user(Role::KETUA_TIM);

        // Pengajuan ulang mandiri: tidak punya kepala_balai_id.
        $p = $this->permohonan(Permohonan::STATUS_PENGAJUAN, [
            'kepala_balai_id' => null,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_PEMOHON,
        ]);

        $this->actingAs($kabalai)->get(route('internal.kabalai.disposisi.index'))
            ->assertOk()->assertSee($p->no_registrasi);
        $this->actingAs($kabalai)->get(route('internal.kabalai.permohonan.index'))
            ->assertOk()->assertSee($p->no_registrasi);
        $this->actingAs($kabalai)->get(route('internal.kabalai.permohonan.show', $p))->assertOk();

        $this->actingAs($kabalai)
            ->post(route('internal.kabalai.disposisi.store', $p), ['ketua_tim_id' => $katim->id])
            ->assertSessionHas('success');

        $this->assertSame(Permohonan::STATUS_DIDISPOSISIKAN, $p->refresh()->status_saat_ini);
    }

    public function test_halaman_pengajuan_ulang_bisa_dibuka_dan_hanya_sekali_per_permohonan_ditutup(): void
    {
        $pbf = $this->pbf();
        $ditutup = $this->permohonan(Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG, ['pbf' => $pbf]);

        // Dulu 404: "baru" tertangkap route `/permohonan/{permohonan}`.
        $this->actingAs($pbf, 'pemohon')->get(route('pemohon.permohonan.create'))->assertOk();

        // Tanpa berkas ditolak.
        $this->actingAs($pbf, 'pemohon')->post(route('pemohon.permohonan.store'), [])
            ->assertSessionHasErrors('dokumen');
        $this->assertSame(1, Permohonan::count());

        $this->actingAs($pbf, 'pemohon')
            ->post(route('pemohon.permohonan.store'), ['dokumen' => [$this->pdf()]])
            ->assertSessionHas('success');

        $baru = Permohonan::where('parent_permohonan_id', $ditutup->id)->first();
        $this->assertNotNull($baru);
        $this->assertSame(Permohonan::STATUS_PENGAJUAN, $baru->status_saat_ini);
        $this->assertSame(Permohonan::DIBUAT_OLEH_PEMOHON, $baru->dibuat_oleh_tipe);

        // Pengajuan ulang kedua dari permohonan ditutup yang sama ditolak.
        $this->actingAs($pbf, 'pemohon')->get(route('pemohon.permohonan.create'))->assertForbidden();
        $this->actingAs($pbf, 'pemohon')
            ->post(route('pemohon.permohonan.store'), ['dokumen' => [$this->pdf()]])
            ->assertForbidden();
        $this->assertSame(1, Permohonan::where('parent_permohonan_id', $ditutup->id)->count());
    }

    // ── Upload revisi ─────────────────────────────────

    public function test_upload_revisi_tanpa_berkas_ditolak_dan_dengan_berkas_kembali_ke_evaluasi(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $pbf = $this->pbf();
        $p = $this->permohonan(Permohonan::STATUS_REVISI_1, ['pbf' => $pbf, 'revisi_ke' => 1]);
        $this->tugaskan($p, $katim, $staff);
        Evaluasi::create(['permohonan_id' => $p->id, 'staff_id' => $staff->id, 'siklus_ke' => 1, 'hasil' => 'tidak_lengkap', 'catatan' => 'Perbaiki denah', 'tanggal_evaluasi' => now()]);

        // Dulu: error 500 (foreach atas null).
        $this->actingAs($pbf, 'pemohon')->post(route('pemohon.revisi.store', $p), [])
            ->assertSessionHasErrors('dokumen');
        $this->assertSame(Permohonan::STATUS_REVISI_1, $p->refresh()->status_saat_ini);

        $this->actingAs($pbf, 'pemohon')
            ->post(route('pemohon.revisi.store', $p), ['dokumen' => [$this->pdf()]])
            ->assertSessionHas('success');

        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->refresh()->status_saat_ini);
        $this->assertSame(1, $p->revisi()->count());

        // Berkas revisi bisa diunduh pemilik, bukan PBF lain.
        $dokumen = $p->revisi->first()->dokumenRevisi->first();
        $this->actingAs($pbf, 'pemohon')->get(route('pemohon.download.revisi', $dokumen))->assertOk();
        $this->actingAs($this->pbf(), 'pemohon')->get(route('pemohon.download.revisi', $dokumen))->assertForbidden();
    }

    // ── M-17: Reassign & reminder Ketua Tim ───────────

    public function test_ketua_tim_bisa_reassign_dan_tetap_satu_staff_aktif(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $katimLain = $this->user(Role::KETUA_TIM);
        $staffA = $this->user(Role::STAFF_SERTIFIKASI);
        $staffB = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_PROSES_EVALUASI);
        $this->tugaskan($p, $katim, $staffA);

        $url = route('internal.ketua_tim.eskalasi.reassign', $p);

        // Bukan Ketua Tim pemilik / bukan Ketua Tim sama sekali.
        $this->actingAs($katimLain)->post($url, ['staff_id' => $staffB->id, 'alasan' => 'Staff cuti panjang'])->assertForbidden();
        $this->actingAs($staffA)->post($url, ['staff_id' => $staffB->id, 'alasan' => 'Staff cuti panjang'])->assertForbidden();

        // Alasan wajib; tujuan harus staff; tidak boleh ke staff yang sama.
        $this->actingAs($katim)->post($url, ['staff_id' => $staffB->id])->assertSessionHasErrors('alasan');
        $this->actingAs($katim)->post($url, ['staff_id' => $katimLain->id, 'alasan' => 'Staff cuti panjang'])->assertSessionHas('error');
        $this->actingAs($katim)->post($url, ['staff_id' => $staffA->id, 'alasan' => 'Staff cuti panjang'])->assertSessionHas('error');
        $this->assertSame($staffA->id, $p->refresh()->distribusiAktif->staff_id);

        $this->actingAs($katim)->post($url, ['staff_id' => $staffB->id, 'alasan' => 'Staff cuti panjang'])->assertSessionHas('success');

        $p->refresh();
        $this->assertSame($staffB->id, $p->distribusiAktif->staff_id);
        $this->assertSame(1, Distribusi::where('permohonan_id', $p->id)->where('is_aktif', true)->count());
        $this->assertSame(2, Distribusi::where('permohonan_id', $p->id)->count()); // riwayat tidak dihapus
        $this->assertSame(Permohonan::STATUS_PROSES_EVALUASI, $p->status_saat_ini); // status & SLA tidak direset
        $this->assertSame(1, StatusLog::where('permohonan_id', $p->id)->count());
        $this->assertDatabaseHas('reassignment_log', ['permohonan_id' => $p->id, 'jenis_aksi' => 'reassign', 'staff_lama_id' => $staffA->id, 'staff_baru_id' => $staffB->id]);
        $this->assertDatabaseHas('audit_trail', ['permohonan_id' => $p->id, 'aksi' => 'reassign_staff', 'modul' => 'M-17']);

        // Staff baru kini berhak mengevaluasi; staff lama tidak lagi.
        $this->actingAs($staffB)->get(route('internal.staff.evaluasi.edit', $p))->assertOk();
        $this->actingAs($staffA)->get(route('internal.staff.evaluasi.edit', $p))->assertForbidden();
    }

    public function test_reassign_ditolak_untuk_permohonan_final(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staffA = $this->user(Role::STAFF_SERTIFIKASI);
        $staffB = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN);
        $this->tugaskan($p, $katim, $staffA);

        $this->actingAs($katim)
            ->post(route('internal.ketua_tim.eskalasi.reassign', $p), ['staff_id' => $staffB->id, 'alasan' => 'Coba alihkan'])
            ->assertSessionHas('error');

        $this->assertSame($staffA->id, $p->refresh()->distribusiAktif->staff_id);
    }

    public function test_reminder_manual_dicatat_dan_dibatasi_jeda(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_PROSES_EVALUASI);
        $this->tugaskan($p, $katim, $staff);
        $url = route('internal.ketua_tim.eskalasi.reminder', $p);

        $this->actingAs($this->user(Role::KETUA_TIM))->post($url)->assertForbidden();

        // Gateway WA mati di test -> dicatat, dengan peringatan gagal kirim.
        $this->actingAs($katim)->post($url, ['catatan' => 'Mohon diprioritaskan'])->assertSessionHas('warning');
        $this->assertDatabaseHas('reassignment_log', ['permohonan_id' => $p->id, 'jenis_aksi' => 'reminder', 'staff_lama_id' => $staff->id]);
        $this->assertDatabaseHas('audit_trail', ['permohonan_id' => $p->id, 'aksi' => 'reminder_manual']);

        // Kiriman kedua dalam jeda ditolak (anti-spam).
        $this->actingAs($katim)->post($url)->assertSessionHas('error');
        $this->assertSame(1, \App\Models\ReassignmentLog::where('jenis_aksi', 'reminder')->count());

        // Saat revisi, yang ditunggu pemohon — reminder ke staff tidak relevan.
        $revisi = $this->permohonan(Permohonan::STATUS_REVISI_1);
        $this->tugaskan($revisi, $katim, $staff);
        $this->actingAs($katim)->post(route('internal.ketua_tim.eskalasi.reminder', $revisi))->assertSessionHas('error');
    }

    public function test_dashboard_ketua_tim_menampilkan_aksi_eskalasi(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $p = $this->permohonan(Permohonan::STATUS_PROSES_EVALUASI);
        $this->tugaskan($p, $katim, $staff);

        $this->actingAs($katim)->get(route('internal.ketua_tim.dashboard'))
            ->assertOk()
            ->assertSee('Alihkan staff')
            ->assertSee('Kirim reminder');
    }

    // ── Hapus permohonan = soft delete ────────────────

    public function test_hapus_permohonan_tidak_menghilangkan_riwayat_dan_audit(): void
    {
        $kabalai = $this->user(Role::KEPALA_BALAI);
        $p = $this->permohonan(Permohonan::STATUS_PENGAJUAN, ['kepala_balai_id' => $kabalai->id]);
        \App\Models\AuditTrail::create(['user_id' => $kabalai->id, 'user_type' => 'internal', 'aksi' => 'status_transition', 'modul' => 'M-07', 'permohonan_id' => $p->id, 'detail' => []]);

        $this->actingAs($kabalai)->delete(route('internal.kabalai.permohonan.destroy', $p))->assertSessionHas('success');

        // Hilang dari daftar & tidak bisa dibuka...
        $this->assertNull(Permohonan::find($p->id));
        $this->actingAs($kabalai)->get(route('internal.kabalai.permohonan.index'))->assertDontSee($p->no_registrasi);
        $this->actingAs($kabalai)->get(route('internal.permohonan.show', $p))->assertNotFound();

        // ...tetapi baris, riwayat status, dan audit trail tetap ada.
        $this->assertNotNull(Permohonan::withTrashed()->find($p->id)->deleted_at);
        $this->assertSame(1, StatusLog::where('permohonan_id', $p->id)->count());
        $this->assertDatabaseHas('audit_trail', ['permohonan_id' => $p->id, 'aksi' => 'status_transition']);
        $this->assertDatabaseHas('audit_trail', ['permohonan_id' => $p->id, 'aksi' => 'permohonan_hapus']);

        // Nomor registrasi milik permohonan terhapus tidak dipakai ulang.
        $tahun = date('Y');
        Permohonan::withTrashed()->whereKey($p->id)->update(['no_registrasi' => "PBF/DENAH/{$tahun}/00007"]);
        $this->assertSame("PBF/DENAH/{$tahun}/00008", Permohonan::generateNoRegistrasi());
    }

    // ── Dashboard Staff ───────────────────────────────

    public function test_statistik_dashboard_staff_hanya_miliknya(): void
    {
        $katim = $this->user(Role::KETUA_TIM);
        $staff = $this->user(Role::STAFF_SERTIFIKASI);
        $staffLain = $this->user(Role::STAFF_SERTIFIKASI);

        $milik = $this->permohonan(Permohonan::STATUS_PROSES_EVALUASI);
        $this->tugaskan($milik, $katim, $staff);
        foreach (range(1, 3) as $i) {
            $this->tugaskan($this->permohonan(Permohonan::STATUS_PROSES_EVALUASI), $katim, $staffLain);
        }

        $response = $this->actingAs($staff)->get(route('internal.staff.dashboard'))->assertOk();

        $this->assertCount(1, $response->viewData('allPermohonans'));
        $this->assertSame($milik->id, $response->viewData('allPermohonans')->first()->id);
    }

    // ── Berkas privat ─────────────────────────────────

    public function test_berkas_baru_tersimpan_privat_dan_berkas_lama_bisa_dipindahkan(): void
    {
        $pbf = $this->pbf();
        $p = $this->permohonan(Permohonan::STATUS_PENGAJUAN, ['pbf' => $pbf]);

        // Berkas lama: masih di disk public (terbuka lewat /storage/...).
        Storage::disk('public')->put('dokumen_permohonan/lama.pdf', '%PDF-lama');
        \App\Models\DokumenPermohonan::create([
            'permohonan_id' => $p->id, 'jenis_dokumen' => 'surat_permohonan', 'nama_file_asli' => 'lama.pdf',
            'path_file' => 'dokumen_permohonan/lama.pdf', 'ukuran_file_kb' => 1, 'mime_type' => 'application/pdf', 'uploaded_at' => now(),
        ]);
        $url = route('pemohon.download.dokumen', [$p, 'surat_permohonan']);

        // Sebelum dipindahkan pun tetap bisa diunduh pemiliknya (fallback), dan hanya lewat login.
        $this->actingAs($pbf, 'pemohon')->get($url)->assertOk();

        $this->artisan('berkas:amankan', ['--dry-run' => true])->assertSuccessful();
        $this->assertTrue(Storage::disk('public')->exists('dokumen_permohonan/lama.pdf'));

        $this->artisan('berkas:amankan')->assertSuccessful();
        $this->assertFalse(Storage::disk('public')->exists('dokumen_permohonan/lama.pdf'));
        $this->assertSame('%PDF-lama', Storage::disk('local')->get('dokumen_permohonan/lama.pdf'));

        // Path di database tidak berubah dan unduhan tetap jalan.
        $this->actingAs($pbf, 'pemohon')->get($url)->assertOk();
        $this->actingAs($this->pbf(), 'pemohon')->get($url)->assertForbidden();

        // Idempoten.
        $this->artisan('berkas:amankan')->assertSuccessful();
    }
}
