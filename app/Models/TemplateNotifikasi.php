<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateNotifikasi extends Model
{
    use HasFactory;

    protected $table = 'template_notifikasi';

    public const DEFAULT_PENGAJUAN_ULANG_WA = "🔁 *Pengajuan Ulang Diterima*\n\nNo. Reg baru: {{no_registrasi}}\nPengajuan ulang dari: {{no_registrasi_lama}}\nPBF: {{nama_pbf}}\n\nBBPOM telah mengajukan ulang permohonan Anda dan sedang diproses. Pantau status di portal menggunakan akun yang sama.";

    public const DEFAULT_PENGAJUAN_ULANG_EMAIL = "Yth. {{nama_pbf}}\n\nBBPOM telah mengajukan ulang permohonan Anda:\n\nNo. Registrasi baru: {{no_registrasi}}\nPengajuan ulang dari: {{no_registrasi_lama}}\nPBF: {{nama_pbf}}\n\nPermohonan sedang diproses. Pantau status di portal menggunakan akun yang sama.\n\nHormat kami,\nTim BBPOM";

    public const DEFAULT_SLA_MENDEKATI_WA ="⏰ *SLA Hampir Habis*\n\nYth. {{nama_staff}},\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\nTahap: {{tahap}}\nBatas waktu: {{deadline}}\nSisa waktu: {{sisa}} hari kerja\n\nMohon segera ditindaklanjuti agar tidak melewati batas SLA.";

    public const DEFAULT_SLA_TERLAMBAT_WA ="🚨 *SLA Terlampaui*\n\nYth. {{nama_staff}},\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\nTahap: {{tahap}}\nBatas waktu: {{deadline}}\nTerlambat: {{keterlambatan}} hari kerja\n\nMohon segera ditindaklanjuti.";

    protected $fillable = [
        'kode_event',
        'channel',
        'subjek',
        'isi_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
