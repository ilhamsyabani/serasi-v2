<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Konfigurasi SLA per tahap (M-16, dikelola Admin IT). Nilai durasi TIDAK
 * boleh di-hardcode di kode aplikasi — selalu ambil dari tabel ini, karena
 * angka SLA berpotensi berubah (sudah beberapa kali direvisi selama
 * pembahasan: 1/7/3 hari, clock-off, dll). Lihat CLAUDE.md §4.
 * Ref: Tahap 4 §3.16
 */
class SlaConfig extends Model
{
    use HasFactory;

    protected $table = 'sla_config';

    public const SATUAN_HARI_KERJA = 'hari_kerja';
    public const SATUAN_HARI_KALENDER = 'hari_kalender';

    protected $fillable = [
        'kode_tahap',
        'nama_tahap',
        'durasi',
        'satuan',
        'clock_off',
        'is_active',
    ];

    protected $casts = [
        'durasi' => 'integer',
        'clock_off' => 'boolean',
    ];
}
