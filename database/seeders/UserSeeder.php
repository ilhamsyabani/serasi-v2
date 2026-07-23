<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::all()->keyBy('kode');

        $users = [
            ['nip' => '198501012010011001', 'nama' => 'Ahmad Subarjo', 'email' => 'kepala.balai@bbpom.test', 'role_kode' => 'kepala_balai'],
            ['nip' => '198703152011012002', 'nama' => 'Rani Wulandari', 'email' => 'ketua.tim@bbpom.test', 'role_kode' => 'ketua_tim'],
            ['nip' => '199002102015031003', 'nama' => 'Budi Santoso', 'email' => 'staff1@bbpom.test', 'role_kode' => 'staff_sertifikasi'],
            ['nip' => '199205202016042004', 'nama' => 'Siti Rahayu', 'email' => 'staff2@bbpom.test', 'role_kode' => 'staff_sertifikasi'],
            ['nip' => '199408302018011005', 'nama' => 'Admin IT', 'email' => 'admin.it@bbpom.test', 'role_kode' => 'admin_it'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'nip' => $u['nip'],
                    'nama' => $u['nama'],
                    'role_id' => $roles[$u['role_kode']]->id,
                    'password' => bcrypt('password'),
                    'sso_identifier' => null,
                    'is_aktif' => true,
                ]
            );
        }
    }
}
