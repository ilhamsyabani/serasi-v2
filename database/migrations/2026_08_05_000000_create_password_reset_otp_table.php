<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_otp', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150);
            $table->string('token', 64)->unique();
            $table->string('kode_otp', 64)->nullable();
            $table->string('channel', 20)->comment('email atau whatsapp');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('status', 20)->default('terkirim');
            $table->timestamps();

            $table->index(['email', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otp');
    }
};
