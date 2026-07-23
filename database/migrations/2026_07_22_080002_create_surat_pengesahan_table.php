<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_pengesahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->unique()->constrained('permohonan');
            $table->foreignId('staff_id')->constrained('users');
            $table->string('path_file', 500);
            $table->string('nama_file_asli', 255);
            $table->string('nomor_surat', 100)->nullable();
            $table->timestamp('tanggal_upload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_pengesahan');
    }
};
