<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateNotifikasi extends Model
{
    use HasFactory;

    protected $table = 'template_notifikasi';

    public const DEFAULT_SLA_TERLAMBAT_WA = "🚨 *SLA Terlampaui*\n\nYth. {{nama_staff}},\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\nTahap: {{tahap}}\nBatas waktu: {{deadline}}\nTerlambat: {{keterlambatan}} hari kerja\n\nMohon segera ditindaklanjuti.";

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
