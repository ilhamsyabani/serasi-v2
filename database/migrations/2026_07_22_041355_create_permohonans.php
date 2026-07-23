<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('no_registrasi', 50)->unique()->comment('Format PBF/DENAH/{tahun}/{nomor_urut} (FR-02)');

            $table->foreignId('pbf_id')->constrained('pbf');

            $table->foreignId('parent_permohonan_id')->nullable()->constrained('permohonan')->comment('Self-reference, riwayat pengajuan ulang (A-05)');

            $table->string('nama_pbf_snapshot', 200)->comment('Snapshot nama saat pengajuan (jaga histori bila master berubah)');
            $table->string('nib_snapshot', 30)->comment('Snapshot NIB');
            $table->string('email_snapshot', 150);
            $table->string('no_wa_snapshot', 20);

            $table->string('status_saat_ini', 50)->comment('Denormalisasi string status (9 nilai baku) — lihat status_master.kode');

            $table->unsignedTinyInteger('revisi_ke')->default(0)->comment('Cache cepat kontrol 3-batas (harus sama dengan MAX(evaluasi.siklus_ke) WHERE hasil=tidak_lengkap)');
            $table->date('tanggal_pengajuan');
            $table->date('sla_deadline_current')->nullable()->comment('Deadline tahap berjalan (dihitung otomatis, hari kerja)');
            $table->boolean('is_overdue')->default(false)->comment('Flag untuk dashboard SLA (M-08)');

            $table->foreignId('kepala_balai_id')->nullable()->constrained('users')->comment('NULL jika pengajuan ulang mandiri oleh pemohon');
            $table->string('dibuat_oleh_tipe', 20)->comment('kepala_balai atau pemohon');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permohonan');
    }
};
