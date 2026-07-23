<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluasi_id')->unique()->constrained('evaluasi');
            $table->foreignId('permohonan_id')->constrained('permohonan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisi');
    }
};
