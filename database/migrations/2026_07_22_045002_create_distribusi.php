<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribusi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan');

            $table->foreignId('ketua_tim_id')->constrained('users');
            $table->foreignId('staff_id')->constrained('users');

            $table->string('jenis', 20)->default('distribusi_awal')->comment('distribusi_awal atau reassignment');
            $table->boolean('is_aktif')->default(true)->comment('Hanya 1 baris aktif per permohonan pada satu waktu');

            $table->dateTime('tanggal');
            $table->dateTime('tanggal_reassign_terakhir')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribusi');
    }
};
