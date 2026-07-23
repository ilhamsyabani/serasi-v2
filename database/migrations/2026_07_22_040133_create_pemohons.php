<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pbf', function (Blueprint $table) {
            $table->id();
            $table->string('nib', 30)->unique();
            $table->string('nama_pbf', 200);
            $table->text('alamat')->nullable();
            $table->string('email', 150);
            $table->string('no_whatsapp', 20);
            $table->string('password_hash', 255);
            $table->boolean('otp_terverifikasi')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pbf');
    }
};
