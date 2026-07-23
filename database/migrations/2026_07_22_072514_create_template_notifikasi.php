<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_event', 50)->comment('Mis. STATUS_PENGAJUAN, STATUS_REVISI, STATUS_TERBIT');
            $table->string('channel', 20)->comment('email atau whatsapp');
            $table->string('subjek', 200)->nullable()->comment('Untuk email');
            $table->text('isi_template')->comment('Mendukung placeholder {{no_registrasi}}, {{nama_pbf}}, dst');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['kode_event', 'channel'], 'template_notifikasi_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_notifikasi');
    }
};
