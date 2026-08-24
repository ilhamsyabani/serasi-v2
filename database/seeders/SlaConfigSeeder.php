<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SlaConfig;

class SlaConfigSeeder extends Seeder
{
    public function run(): void
    {
        $slas = [
            ['kode_tahap' => 'pengajuan', 'nama_tahap' => 'Pengajuan', 'durasi' => 1, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true],
            ['kode_tahap' => 'didisposisikan', 'nama_tahap' => 'Didisposisikan', 'durasi' => 1, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true],
            ['kode_tahap' => 'proses_evaluasi', 'nama_tahap' => 'Proses Evaluasi', 'durasi' => 7, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true],
            ['kode_tahap' => 'revisi_1', 'nama_tahap' => 'Revisi ke-1', 'durasi' => null, 'satuan' => 'hari_kerja', 'clock_off' => true, 'is_active' => true],
            ['kode_tahap' => 'revisi_2', 'nama_tahap' => 'Revisi ke-2', 'durasi' => null, 'satuan' => 'hari_kerja', 'clock_off' => true, 'is_active' => true],
            ['kode_tahap' => 'revisi_3', 'nama_tahap' => 'Revisi ke-3', 'durasi' => null, 'satuan' => 'hari_kerja', 'clock_off' => true, 'is_active' => true],
            ['kode_tahap' => 'ditutup_pengajuan_ulang', 'nama_tahap' => 'Ditutup – Perlu Pengajuan Ulang', 'durasi' => null, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true],
            ['kode_tahap' => 'menunggu_surat_pengesahan', 'nama_tahap' => 'Menunggu Surat Pengesahan', 'durasi' => 3, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true],
            ['kode_tahap' => 'terbit_surat_pengesahan', 'nama_tahap' => 'Terbit Surat Pengesahan', 'durasi' => 1, 'satuan' => 'hari_kerja', 'clock_off' => false, 'is_active' => true],
        ];

        foreach ($slas as $sla) {
            SlaConfig::updateOrCreate(['kode_tahap' => $sla['kode_tahap']], $sla);
        }
    }
}
