<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HariLibur;

class HariLiburSeeder extends Seeder
{
    public function run(): void
    {
        $liburs = [
            ['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru 2026'],
            ['tanggal' => '2026-03-14', 'keterangan' => 'Hari Raya Nyepi'],
            ['tanggal' => '2026-03-25', 'keterangan' => 'Hari Raya Idul Fitri 1447H'],
            ['tanggal' => '2026-03-26', 'keterangan' => 'Hari Raya Idul Fitri 1447H'],
            ['tanggal' => '2026-04-06', 'keterangan' => 'Wafat Yesus Kristus'],
            ['tanggal' => '2026-05-01', 'keterangan' => 'Hari Buruh Internasional'],
            ['tanggal' => '2026-05-14', 'keterangan' => 'Kenaikan Yesus Kristus'],
            ['tanggal' => '2026-05-21', 'keterangan' => 'Hari Raya Waisak 2570 BE'],
            ['tanggal' => '2026-05-25', 'keterangan' => 'Hari Raya Idul Adha 1447H'],
            ['tanggal' => '2026-06-01', 'keterangan' => 'Hari Lahir Pancasila'],
            ['tanggal' => '2026-08-17', 'keterangan' => 'Hari Kemerdekaan RI'],
            ['tanggal' => '2026-12-25', 'keterangan' => 'Hari Raya Natal'],
        ];

        foreach ($liburs as $libur) {
            HariLibur::updateOrCreate(['tanggal' => $libur['tanggal']], $libur);
        }
    }
}
