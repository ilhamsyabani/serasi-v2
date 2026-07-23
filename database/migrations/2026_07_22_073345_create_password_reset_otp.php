<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pbf_id')->constrained('pbf');
            $table->string('kode_otp', 10)->comment('Disimpan hash, bukan plaintext');
            $table->string('channel', 20)->comment('email atau whatsapp');
            $table->string('status', 20)->comment('terkirim, terverifikasi, kedaluwarsa');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_log');
    }
};
