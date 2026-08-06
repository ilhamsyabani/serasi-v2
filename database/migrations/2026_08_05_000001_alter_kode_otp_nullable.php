<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_otp', function (Blueprint $table) {
            $table->string('kode_otp', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_otp', function (Blueprint $table) {
            $table->string('kode_otp', 64)->nullable(false)->change();
        });
    }
};
