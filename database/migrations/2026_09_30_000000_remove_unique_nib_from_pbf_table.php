<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbf', function (Blueprint $table) {
            $table->string('nib', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pbf', function (Blueprint $table) {
            $table->string('nib', 30)->unique()->change();
        });
    }
};
