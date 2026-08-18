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

        // Data aktual dari BBPOM Surabaya
        $users = [
            [
                'nip' => '197511292000031001',
                'nama' => 'Yudi Noviandi, M.Sc.Tech, Apt',
                'email' => 'kepala.balai@bbpom.test',
                'no_whatsapp' => null,
                'role_kode' => 'kepala_balai',
            ],
            [
                'nip' => '198512182008122003',
                'nama' => 'Irma Rahmawati, S.Farm, Apt.',
                'email' => 'ketua.tim@bbpom.test',
                'no_whatsapp' => '085645397002',
                'role_kode' => 'ketua_tim',
            ],
            [
                'nip' => '198503122008122003',
                'nama' => 'Rizka Marufah, S.Farm, Apt',
                'email' => 'staff1@bbpom.test',
                'no_whatsapp' => '082189016466',
                'role_kode' => 'staff_sertifikasi',
            ],
            [
                'nip' => '199610232023212024',
                'nama' => 'Dian Fajryanti Jatiningrum, S.Farm, Apt',
                'email' => 'staff2@bbpom.test',
                'no_whatsapp' => '08996372950',
                'role_kode' => 'staff_sertifikasi',
            ],
            [
                'nip' => '199506072019032007',
                'nama' => 'Admin IT',
                'email' => 'admin.it@bbpom.test',
                'no_whatsapp' => null,
                'role_kode' => 'admin_it',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'nip' => $u['nip'],
                    'nama' => $u['nama'],
                    'no_whatsapp' => $u['no_whatsapp'],
                    'role_id' => $roles[$u['role_kode']]->id,
                    'password' => bcrypt('password'),
                    'sso_identifier' => null,
                    'is_aktif' => true,
                ]
            );
        }
    }
}
