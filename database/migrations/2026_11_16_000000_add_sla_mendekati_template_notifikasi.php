<?php

use App\Models\TemplateNotifikasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Template WA pengingat otomatis "SLA hampir habis" ke staff (notifikasi:sla-reminder).
 * Terpisah dari REMINDER (pengingat manual Ketua Tim) agar isinya bisa memuat
 * tahap, batas waktu, dan sisa hari kerja.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('template_notifikasi')->insertOrIgnore([
            'kode_event' => 'SLA_MENDEKATI',
            'channel' => 'whatsapp',
            'subjek' => '',
            'isi_template' => TemplateNotifikasi::DEFAULT_SLA_MENDEKATI_WA,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('template_notifikasi')
            ->where('kode_event', 'SLA_MENDEKATI')
            ->where('channel', 'whatsapp')
            ->delete();
    }
};
