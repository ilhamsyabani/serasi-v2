<?php

namespace Database\Seeders;

use App\Models\Disposisi;
use App\Models\Distribusi;
use App\Models\DokumenPermohonan;
use App\Models\Evaluasi;
use App\Models\Pbf;
use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Models\SuratPengesahan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $kabalai = User::whereHas('role', fn($q) => $q->where('kode', 'kepala_balai'))->first();
        $ketuaTim = User::whereHas('role', fn($q) => $q->where('kode', 'ketua_tim'))->first();
        $staff1 = User::whereHas('role', fn($q) => $q->where('kode', 'staff_sertifikasi'))->first();
        $pbf1 = Pbf::first();

        $jenis = ['surat_permohonan', 'surat_pernyataan', 'rancangan_denah', 'izin_pbf', 'stra_pj'];

        // 1. Pengajuan Baru
        $p1 = Permohonan::create([
            'no_registrasi' => 'PBF/DENAH/2026/00001',
            'pbf_id' => $pbf1->id,
            'nama_pbf_snapshot' => 'PT. Contoh Farma',
            'nib_snapshot' => '1234567890123',
            'email_snapshot' => 'pemohon@contohfarma.test',
            'no_wa_snapshot' => '081234567890',
            'status_saat_ini' => Permohonan::STATUS_PENGAJUAN,
            'tanggal_pengajuan' => now()->subDays(2),
            'kepala_balai_id' => $kabalai->id,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p1->id, 'status' => 'pengajuan', 'waktu_mulai' => now()->subDays(2)]);
        $this->addDokumen($p1, array_slice($jenis, 0, 3));

        // 2. Didisposisikan
        $p2 = Permohonan::create([
            'no_registrasi' => 'PBF/DENAH/2026/00002',
            'pbf_id' => $pbf1->id,
            'nama_pbf_snapshot' => 'PT. Contoh Farma',
            'nib_snapshot' => '1234567890123',
            'email_snapshot' => 'pemohon@contohfarma.test',
            'no_wa_snapshot' => '081234567890',
            'status_saat_ini' => Permohonan::STATUS_DIDISPOSISIKAN,
            'tanggal_pengajuan' => now()->subDays(5),
            'kepala_balai_id' => $kabalai->id,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p2->id, 'status' => 'pengajuan', 'waktu_mulai' => now()->subDays(5)]);
        StatusLog::create(['permohonan_id' => $p2->id, 'status' => 'didisposisikan', 'waktu_mulai' => now()->subDays(3)]);
        $this->addDokumen($p2, $jenis);
        Disposisi::create(['permohonan_id' => $p2->id, 'kepala_balai_id' => $kabalai->id, 'ketua_tim_id' => $ketuaTim->id, 'catatan' => 'Prioritas tinggi', 'tanggal_disposisi' => now()->subDays(3)]);

        // 3. Proses Evaluasi (langsung, tanpa revisi)
        $p3 = Permohonan::create([
            'no_registrasi' => 'PBF/DENAH/2026/00003',
            'pbf_id' => $pbf1->id,
            'nama_pbf_snapshot' => 'PT. Contoh Farma',
            'nib_snapshot' => '1234567890123',
            'email_snapshot' => 'pemohon@contohfarma.test',
            'no_wa_snapshot' => '081234567890',
            'status_saat_ini' => Permohonan::STATUS_PROSES_EVALUASI,
            'tanggal_pengajuan' => now()->subDays(12),
            'kepala_balai_id' => $kabalai->id,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p3->id, 'status' => 'pengajuan', 'waktu_mulai' => now()->subDays(12)]);
        StatusLog::create(['permohonan_id' => $p3->id, 'status' => 'didisposisikan', 'waktu_mulai' => now()->subDays(10)]);
        StatusLog::create(['permohonan_id' => $p3->id, 'status' => 'proses_evaluasi', 'waktu_mulai' => now()->subDays(7)]);
        $this->addDokumen($p3, $jenis);
        Disposisi::create(['permohonan_id' => $p3->id, 'kepala_balai_id' => $kabalai->id, 'ketua_tim_id' => $ketuaTim->id, 'tanggal_disposisi' => now()->subDays(10)]);
        Distribusi::create(['permohonan_id' => $p3->id, 'ketua_tim_id' => $ketuaTim->id, 'staff_id' => $staff1->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()->subDays(7)]);

        // 4. Revisi ke-2 (sudah 1x revisi gagal)
        $p4 = Permohonan::create([
            'no_registrasi' => 'PBF/DENAH/2026/00004',
            'pbf_id' => $pbf1->id,
            'nama_pbf_snapshot' => 'PT. Contoh Farma',
            'nib_snapshot' => '1234567890123',
            'email_snapshot' => 'pemohon@contohfarma.test',
            'no_wa_snapshot' => '081234567890',
            'status_saat_ini' => Permohonan::STATUS_REVISI_2,
            'tanggal_pengajuan' => now()->subDays(20),
            'kepala_balai_id' => $kabalai->id,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p4->id, 'status' => 'pengajuan', 'waktu_mulai' => now()->subDays(20)]);
        StatusLog::create(['permohonan_id' => $p4->id, 'status' => 'didisposisikan', 'waktu_mulai' => now()->subDays(18)]);
        StatusLog::create(['permohonan_id' => $p4->id, 'status' => 'proses_evaluasi', 'waktu_mulai' => now()->subDays(15)]);
        StatusLog::create(['permohonan_id' => $p4->id, 'status' => 'revisi_1', 'waktu_mulai' => now()->subDays(12), 'is_clock_off' => true]);
        StatusLog::create(['permohonan_id' => $p4->id, 'status' => 'proses_evaluasi', 'waktu_mulai' => now()->subDays(10)]);
        StatusLog::create(['permohonan_id' => $p4->id, 'status' => 'revisi_2', 'waktu_mulai' => now()->subDays(7), 'is_clock_off' => true]);
        $this->addDokumen($p4, $jenis);
        Disposisi::create(['permohonan_id' => $p4->id, 'kepala_balai_id' => $kabalai->id, 'ketua_tim_id' => $ketuaTim->id, 'tanggal_disposisi' => now()->subDays(18)]);
        Distribusi::create(['permohonan_id' => $p4->id, 'ketua_tim_id' => $ketuaTim->id, 'staff_id' => $staff1->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()->subDays(15)]);
        Evaluasi::create(['permohonan_id' => $p4->id, 'staff_id' => $staff1->id, 'siklus_ke' => 0, 'hasil' => 'tidak_lengkap', 'catatan' => 'Denah tidak sesuai standar BBPOM. Mohon perbaiki skala dan legenda.', 'tanggal_evaluasi' => now()->subDays(15)]);
        Evaluasi::create(['permohonan_id' => $p4->id, 'staff_id' => $staff1->id, 'siklus_ke' => 1, 'hasil' => 'tidak_lengkap', 'catatan' => 'Denah sudah bagus, tapi缺少 tanda north arrow dan skala.', 'tanggal_evaluasi' => now()->subDays(10)]);

        // 5. Menunggu Surat Pengesahan
        $p5 = Permohonan::create([
            'no_registrasi' => 'PBF/DENAH/2026/00005',
            'pbf_id' => $pbf1->id,
            'nama_pbf_snapshot' => 'PT. Contoh Farma',
            'nib_snapshot' => '1234567890123',
            'email_snapshot' => 'pemohon@contohfarma.test',
            'no_wa_snapshot' => '081234567890',
            'status_saat_ini' => Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
            'tanggal_pengajuan' => now()->subDays(15),
            'kepala_balai_id' => $kabalai->id,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p5->id, 'status' => 'pengajuan', 'waktu_mulai' => now()->subDays(15)]);
        StatusLog::create(['permohonan_id' => $p5->id, 'status' => 'didisposisikan', 'waktu_mulai' => now()->subDays(13)]);
        StatusLog::create(['permohonan_id' => $p5->id, 'status' => 'proses_evaluasi', 'waktu_mulai' => now()->subDays(11)]);
        StatusLog::create(['permohonan_id' => $p5->id, 'status' => 'menunggu_surat_pengesahan', 'waktu_mulai' => now()->subDays(2)]);
        $this->addDokumen($p5, $jenis);
        Disposisi::create(['permohonan_id' => $p5->id, 'kepala_balai_id' => $kabalai->id, 'ketua_tim_id' => $ketuaTim->id, 'tanggal_disposisi' => now()->subDays(13)]);
        Distribusi::create(['permohonan_id' => $p5->id, 'ketua_tim_id' => $ketuaTim->id, 'staff_id' => $staff1->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()->subDays(11)]);
        Evaluasi::create(['permohonan_id' => $p5->id, 'staff_id' => $staff1->id, 'siklus_ke' => 0, 'hasil' => 'lengkap', 'catatan' => 'Semua dokumen sesuai. Silakan proses penerbitan surat.', 'tanggal_evaluasi' => now()->subDays(2)]);

        // 6. Terbit (SELESAI)
        $p6 = Permohonan::create([
            'no_registrasi' => 'PBF/DENAH/2026/00006',
            'pbf_id' => $pbf1->id,
            'nama_pbf_snapshot' => 'PT. Contoh Farma',
            'nib_snapshot' => '1234567890123',
            'email_snapshot' => 'pemohon@contohfarma.test',
            'no_wa_snapshot' => '081234567890',
            'status_saat_ini' => Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN,
            'tanggal_pengajuan' => now()->subDays(30),
            'kepala_balai_id' => $kabalai->id,
            'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
        ]);
        StatusLog::create(['permohonan_id' => $p6->id, 'status' => 'pengajuan', 'waktu_mulai' => now()->subDays(30)]);
        StatusLog::create(['permohonan_id' => $p6->id, 'status' => 'didisposisikan', 'waktu_mulai' => now()->subDays(28)]);
        StatusLog::create(['permohonan_id' => $p6->id, 'status' => 'proses_evaluasi', 'waktu_mulai' => now()->subDays(25)]);
        StatusLog::create(['permohonan_id' => $p6->id, 'status' => 'menunggu_surat_pengesahan', 'waktu_mulai' => now()->subDays(5)]);
        StatusLog::create(['permohonan_id' => $p6->id, 'status' => 'terbit_surat_pengesahan', 'waktu_mulai' => now()->subDays(3)]);
        $this->addDokumen($p6, $jenis);
        Disposisi::create(['permohonan_id' => $p6->id, 'kepala_balai_id' => $kabalai->id, 'ketua_tim_id' => $ketuaTim->id, 'tanggal_disposisi' => now()->subDays(28)]);
        Distribusi::create(['permohonan_id' => $p6->id, 'ketua_tim_id' => $ketuaTim->id, 'staff_id' => $staff1->id, 'jenis' => 'distribusi_awal', 'is_aktif' => true, 'tanggal' => now()->subDays(25)]);
        Evaluasi::create(['permohonan_id' => $p6->id, 'staff_id' => $staff1->id, 'siklus_ke' => 0, 'hasil' => 'lengkap', 'tanggal_evaluasi' => now()->subDays(5)]);
        SuratPengesahan::create([
            'permohonan_id' => $p6->id,
            'staff_id' => $staff1->id,
            'path_file' => 'demo/surat_dummy.pdf',
            'nama_file_asli' => 'Surat_Pengesahan_PBF_2026.pdf',
            'nomor_surat' => '123/PBF/DENAH/BBPOM/2026',
            'tanggal_upload' => now()->subDays(3),
        ]);
    }

    private function addDokumen(Permohonan $p, array $jenis): void
    {
        foreach ($jenis as $j) {
            DokumenPermohonan::create([
                'permohonan_id' => $p->id,
                'jenis_dokumen' => $j,
                'nama_file_asli' => "demo_{$j}.pdf",
                'path_file' => "demo/{$j}.pdf",
                'ukuran_file_kb' => rand(100, 2000),
                'mime_type' => 'application/pdf',
                'uploaded_at' => now(),
            ]);
        }
    }
}
