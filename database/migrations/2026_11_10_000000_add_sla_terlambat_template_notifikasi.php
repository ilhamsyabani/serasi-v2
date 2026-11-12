<?php

use App\Models\TemplateNotifikasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('template_notifikasi')->insertOrIgnore([
            'kode_event' => 'SLA_TERLAMBAT',
            'channel' => 'whatsapp',
            'subjek' => '',
            'isi_template' => TemplateNotifikasi::DEFAULT_SLA_TERLAMBAT_WA,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('template_notifikasi')
            ->where('kode_event', 'SLA_TERLAMBAT')
            ->where('channel', 'whatsapp')
            ->delete();
    }
};
