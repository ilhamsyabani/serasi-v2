<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Batas revisi dikembalikan dari 4 menjadi 3 (Permohonan::MAKS_REVISI).
 * Membatalkan data master dari migration 2026_11_13 (status revisi_4 di
 * status_master & sla_config, urutan status). Template penutupan tetap memakai
 * placeholder {{maks_revisi}}, yang kini otomatis berisi 3.
 *
 * Bila ternyata ada permohonan yang sempat masuk revisi_4, baris master TIDAK
 * dihapus agar label & riwayatnya tetap bisa ditampilkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $dipakai = DB::table('permohonan')->where('status_saat_ini', 'revisi_4')->exists()
            || DB::table('status_log')->where('status', 'revisi_4')->exists();

        if (! $dipakai) {
            DB::table('status_master')->where('kode', 'revisi_4')->delete();
            DB::table('sla_config')->where('kode_tahap', 'revisi_4')->delete();

            foreach (['ditutup_pengajuan_ulang' => 7, 'menunggu_surat_pengesahan' => 8, 'terbit_surat_pengesahan' => 9] as $kode => $urutan) {
                DB::table('status_master')->where('kode', $kode)->update(['urutan' => $urutan]);
            }
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: batas 4 revisi sudah dibatalkan.
    }
};
