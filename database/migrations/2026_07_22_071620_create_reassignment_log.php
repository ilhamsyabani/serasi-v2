<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reassignment_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan');

            $table->foreignId('ketua_tim_id')->constrained('users');
            $table->foreignId('staff_lama_id')->nullable()->constrained('users');
            $table->foreignId('staff_baru_id')->nullable()->constrained('users');

            $table->string('jenis_aksi', 20)->comment('reassign, reminder, lainnya');
            $table->text('alasan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reassignment_log');
    }
};
