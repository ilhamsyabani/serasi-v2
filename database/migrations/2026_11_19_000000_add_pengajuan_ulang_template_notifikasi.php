<?php

use App\Models\TemplateNotifikasi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Template notifikasi ke pemohon saat Kepala Balai mengajukan ulang permohonan
 * yang ditutup (App\Services\PengajuanUlangService). Tanpa kredensial — akun sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('template_notifikasi')->insertOrIgnore([
            [
                'kode_event' => 'PENGAJUAN_ULANG',
                'channel' => 'whatsapp',
                'subjek' => '',
                'isi_template' => TemplateNotifikasi::DEFAULT_PENGAJUAN_ULANG_WA,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode_event' => 'PENGAJUAN_ULANG',
                'channel' => 'email',
                'subjek' => 'Pengajuan Ulang Diterima - SERASI',
                'isi_template' => TemplateNotifikasi::DEFAULT_PENGAJUAN_ULANG_EMAIL,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('template_notifikasi')->where('kode_event', 'PENGAJUAN_ULANG')->delete();
    }
};
