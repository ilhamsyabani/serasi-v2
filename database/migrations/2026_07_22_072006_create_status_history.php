<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan');

            $table->string('status', 50)->comment('Salah satu dari 9 status baku');
            $table->timestamp('waktu_mulai');
            $table->timestamp('waktu_selesai')->nullable()->comment('NULL jika status masih berjalan');
            $table->boolean('is_clock_off')->default(false)->comment('TRUE khusus status Revisi ke-1/2/3 (SLA tidak dihitung)');
            $table->integer('durasi_hari_kerja')->nullable()->comment('Dihitung backend saat waktu_selesai terisi, mengacu ke sla_config & hari_libur');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_log');
    }
};
