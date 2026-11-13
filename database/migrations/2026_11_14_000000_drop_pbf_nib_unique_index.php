<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 2026_09_30 memakai ->change() yang TIDAK menghapus index unique,
 * sehingga pada database baru (mis. SQLite lokal) pbf_nib_unique masih ada.
 * Satu NIB boleh dipakai beberapa cabang PBF, jadi index dihapus bila masih ada.
 * Di server yang index-nya sudah tidak ada, migration ini tidak mengubah apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('pbf', 'pbf_nib_unique')) {
            Schema::table('pbf', function (Blueprint $table) {
                $table->dropUnique('pbf_nib_unique');
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak mengembalikan unique: data produksi sudah berisi NIB ganda.
    }
};
