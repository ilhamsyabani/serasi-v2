<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_trail', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable()->comment('FK ke users.id atau pbf.id sesuai user_type');
            $table->string('user_type', 20)->comment('internal atau pemohon');

            $table->string('aksi', 100)->comment('mis. input_permohonan, upload_revisi, reassign_staff');
            $table->string('modul', 50)->comment('Kode modul M-01 s.d. M-17');
            $table->foreignId('permohonan_id')->nullable()->constrained('permohonan');

            $table->json('detail')->nullable()->comment('Payload perubahan (before/after bila relevan)');
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trail');
    }
};
