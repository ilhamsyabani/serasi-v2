<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pbf;

class PbfSeeder extends Seeder
{
    public function run(): void
    {
        Pbf::create([
            'nib' => '1234567890123',
            'nama_pbf' => 'PT. Contoh Farma',
            'alamat' => 'Jl. Mawar No. 1, Jakarta Selatan',
            'email' => 'pemohon@contohfarma.test',
            'no_whatsapp' => '081234567890',
            'password_hash' => bcrypt('password'),
            'otp_terverifikasi' => true,
        ]);
    }
}
