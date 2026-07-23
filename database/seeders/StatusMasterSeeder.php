<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatusMaster;

class StatusMasterSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['kode' => 'pengajuan', 'nama' => 'Pengajuan', 'urutan' => 1, 'is_final' => false, 'is_clockoff' => false],
            ['kode' => 'didisposisikan', 'nama' => 'Didisposisikan', 'urutan' => 2, 'is_final' => false, 'is_clockoff' => false],
            ['kode' => 'proses_evaluasi', 'nama' => 'Proses Evaluasi', 'urutan' => 3, 'is_final' => false, 'is_clockoff' => false],
            ['kode' => 'revisi_1', 'nama' => 'Revisi ke-1', 'urutan' => 4, 'is_final' => false, 'is_clockoff' => true],
            ['kode' => 'revisi_2', 'nama' => 'Revisi ke-2', 'urutan' => 5, 'is_final' => false, 'is_clockoff' => true],
            ['kode' => 'revisi_3', 'nama' => 'Revisi ke-3', 'urutan' => 6, 'is_final' => false, 'is_clockoff' => true],
            ['kode' => 'ditutup_pengajuan_ulang', 'nama' => 'Ditutup – Perlu Pengajuan Ulang', 'urutan' => 7, 'is_final' => true, 'is_clockoff' => false],
            ['kode' => 'menunggu_surat_pengesahan', 'nama' => 'Menunggu Surat Pengesahan', 'urutan' => 8, 'is_final' => false, 'is_clockoff' => false],
            ['kode' => 'terbit_surat_pengesahan', 'nama' => 'Terbit Surat Pengesahan', 'urutan' => 9, 'is_final' => true, 'is_clockoff' => false],
        ];

        foreach ($statuses as $status) {
            StatusMaster::updateOrCreate(['kode' => $status['kode']], $status);
        }
    }
}
