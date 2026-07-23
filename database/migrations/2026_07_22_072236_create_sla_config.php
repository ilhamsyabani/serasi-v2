<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_config', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tahap', 50)->unique();
            $table->string('nama_tahap', 100);
            $table->integer('durasi')->nullable()->comment('Nilai numerik (1, 7, 3, dst — null jika clock-off/tidak berlaku)');
            $table->string('satuan', 20)->default('hari_kerja')->comment('hari_kerja atau hari_kalender');
            $table->boolean('clock_off')->default(false)->comment('TRUE untuk tahap Revisi');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_config');
    }
};
