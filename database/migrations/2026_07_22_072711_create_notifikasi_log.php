<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->nullable()->constrained('permohonan')->comment('NULL jika notifikasi bersifat umum/sistem');

            $table->string('tujuan_tipe', 20)->comment('pemohon, staff, ketua_tim, kepala_balai');
            $table->unsignedBigInteger('tujuan_id')->comment('Merujuk ke pbf.id atau users.id tergantung tujuan_tipe');

            $table->string('channel', 20)->comment('email atau whatsapp');
            $table->string('template_kode', 50)->nullable()->comment('Merujuk ke template di M-16');
            $table->string('status_kirim', 20)->default('pending')->comment('terkirim, gagal, pending');
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
