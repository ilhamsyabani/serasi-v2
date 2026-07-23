<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            StatusMasterSeeder::class,
            SlaConfigSeeder::class,
            HariLiburSeeder::class,
            TemplateNotifikasiSeeder::class,
            UserSeeder::class,
            PbfSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
