<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_revisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revisi_id')->constrained('revisi');
            $table->string('jenis_dokumen', 50)->nullable()->comment('Kode dokumen yang direvisi (opsional)');
            $table->string('nama_file_asli', 255);
            $table->string('path_file', 500);
            $table->integer('ukuran_file_kb');
            $table->string('mime_type', 100);
            $table->string('checksum', 64)->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_revisi');
    }
};
