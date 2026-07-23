<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan');
            $table->foreignId('staff_id')->constrained('users');
            $table->tinyInteger('siklus_ke')->comment('0 = evaluasi awal, 1-3 = evaluasi setelah revisi ke-N');
            $table->string('hasil', 20)->comment('lengkap atau tidak_lengkap');
            $table->text('catatan')->nullable()->comment('Catatan ketidaksesuaian (hanya diisi jika hasil=Tidak Lengkap)');
            $table->dateTime('tanggal_evaluasi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi');
    }
};
