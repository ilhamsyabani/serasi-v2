<?php

namespace Database\Seeders;

use App\Models\ConfigSetting;
use Illuminate\Database\Seeder;

class ConfigSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'otp_pemohon_enabled', 'value' => 'false', 'group' => 'auth', 'description' => 'Aktifkan verifikasi OTP saat login pemohon. Nonaktifkan = login langsung tanpa OTP.'],
        ];

        foreach ($settings as $setting) {
            ConfigSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
