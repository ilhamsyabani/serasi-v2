<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['kode' => 'kepala_balai', 'nama' => 'Kepala Balai'],
            ['kode' => 'ketua_tim', 'nama' => 'Ketua Tim Sertifikasi'],
            ['kode' => 'staff_sertifikasi', 'nama' => 'Staff Sertifikasi'],
            ['kode' => 'admin_it', 'nama' => 'Administrator IT'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['kode' => $role['kode']], $role);
        }
    }
}
