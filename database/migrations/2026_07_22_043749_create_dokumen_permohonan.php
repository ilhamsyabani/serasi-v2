<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_permohonan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan');

            $table->string('jenis_dokumen', 50)->comment('Kode dokumen — lihat konstanta DokumenPermohonan');
            $table->tinyInteger('versi')->default(1);
            $table->string('nama_file_asli', 255);
            $table->string('path_file', 500);
            $table->integer('ukuran_file_kb');
            $table->string('mime_type', 100);
            $table->string('checksum', 64)->nullable();

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users');
            $table->foreignId('uploaded_by_pemohon_id')->nullable()->constrained('pbf');

            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_permohonan');
    }
};
