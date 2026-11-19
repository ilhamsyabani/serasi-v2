<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kolom `uuid` sebagai pengenal publik di URL (lihat App\Models\Concerns\HasPublicUuid),
 * agar ID angka berurutan tidak terekspos. Primary key & foreign key tidak berubah.
 * Baris lama diisi UUID v4 acak.
 */
return new class extends Migration
{
    private const TABEL = ['permohonan', 'dokumen_revisi', 'notifikasi', 'users', 'hari_libur', 'sla_config'];

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            if (! Schema::hasColumn($tabel, 'uuid')) {
                Schema::table($tabel, function (Blueprint $table) {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }

            DB::table($tabel)->whereNull('uuid')->orderBy('id')->select('id')
                ->chunkById(500, function ($rows) use ($tabel) {
                    foreach ($rows as $row) {
                        DB::table($tabel)->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
                    }
                });

            Schema::table($tabel, function (Blueprint $table) use ($tabel) {
                $table->unique('uuid', "{$tabel}_uuid_unique");
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) use ($tabel) {
                $table->dropUnique("{$tabel}_uuid_unique");
                $table->dropColumn('uuid');
            });
        }
    }
};
