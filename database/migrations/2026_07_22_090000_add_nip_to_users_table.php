<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NIP dibuat nullable agar migrasi aman dijalankan pada tabel `users` yang
     * sudah berisi data (kolom NOT NULL + unique akan tabrakan pada string kosong).
     * Baris lama di-backfill dengan NIP dummy yang selaras dengan UserSeeder.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 30)->nullable()->unique()->after('role_id')->comment('Nomer Induk Pegawai — login identifier');
        });

        $backfill = [
            'kepala.balai@bbpom.test' => '198501012010011001',
            'ketua.tim@bbpom.test'    => '198703152011012002',
            'staff1@bbpom.test'       => '199002102015031003',
            'staff2@bbpom.test'       => '199205202016042004',
            'admin.it@bbpom.test'     => '199408302018011005',
        ];

        foreach ($backfill as $email => $nip) {
            DB::table('users')->where('email', $email)->whereNull('nip')->update(['nip' => $nip]);
        }

        // Baris lain yang belum punya NIP: pakai placeholder unik berbasis id agar tetap bisa login setelah di-update Admin IT.
        DB::table('users')->whereNull('nip')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['nip' => 'TMP-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nip');
        });
    }
};
