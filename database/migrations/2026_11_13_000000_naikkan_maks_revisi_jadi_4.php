<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Batas revisi naik dari 3 menjadi 4 (Permohonan::MAKS_REVISI).
 *
 * Hanya MENAMBAH data master (status_master, sla_config);
 * tidak mengubah data permohonan yang sudah berjalan. Teks template yang sudah
 * diedit Admin IT tidak ditimpa — hanya angka "3" pada teks default yang diganti
 * placeholder {{maks_revisi}}.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Geser urutan status sesudah revisi agar revisi_4 menempati urutan 7.
        foreach (['ditutup_pengajuan_ulang' => 8, 'menunggu_surat_pengesahan' => 9, 'terbit_surat_pengesahan' => 10] as $kode => $urutan) {
            DB::table('status_master')->where('kode', $kode)->update(['urutan' => $urutan]);
        }

        DB::table('status_master')->insertOrIgnore([
            'kode' => 'revisi_4',
            'nama' => 'Revisi ke-4',
            'urutan' => 7,
            'is_final' => false,
            'is_clockoff' => true,
        ] + $this->timestamps('status_master', $now));

        DB::table('sla_config')->insertOrIgnore([
            'kode_tahap' => 'revisi_4',
            'nama_tahap' => 'Revisi ke-4',
            'durasi' => null,
            'satuan' => 'hari_kerja',
            'clock_off' => true,
            'is_active' => true,
        ] + $this->timestamps('sla_config', $now));

        // Teks penutupan default menyebut "3 kali revisi" — ganti dengan placeholder.
        DB::table('template_notifikasi')
            ->where('kode_event', 'DITUTUP_PENGAJUAN_ULANG')
            ->get()
            ->each(function ($row) {
                $baru = str_replace(
                    ['setelah 3 (tiga) kali revisi', 'Setelah 3 kali revisi'],
                    ['setelah {{maks_revisi}} kali revisi', 'Setelah {{maks_revisi}} kali revisi'],
                    $row->isi_template
                );
                if ($baru !== $row->isi_template) {
                    DB::table('template_notifikasi')->where('id', $row->id)->update(['isi_template' => $baru]);
                }
            });
    }

    public function down(): void
    {
        DB::table('sla_config')->where('kode_tahap', 'revisi_4')->delete();
        DB::table('status_master')->where('kode', 'revisi_4')->delete();
    }

    private function timestamps(string $table, $now): array
    {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, 'created_at')
            ? ['created_at' => $now, 'updated_at' => $now]
            : [];
    }
};
