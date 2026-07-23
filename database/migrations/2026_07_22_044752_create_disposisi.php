<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('disposisi', function (Blueprint $table) {
            $table->id();
            // Relasi ke Permohonan (1:1 / Unique)
            $table->foreignId('permohonan_id')
                  ->unique()
                  ->constrained('permohonan')
                  ->comment('1:1 sesuai A-01');
            
            // Relasi ke Users
            $table->foreignId('kepala_balai_id')->constrained('users');
            $table->foreignId('ketua_tim_id')->constrained('users');
            
            // Teks (Nullable)
            $table->text('catatan')->nullable()->comment('Catatan disposisi opsional');
            
            // Tanggal Disposisi
            $table->dateTime('tanggal_disposisi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disposisi');
    }
};
