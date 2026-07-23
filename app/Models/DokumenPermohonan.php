<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 5 jenis dokumen wajib permohonan (Tahap 1).
 * `versi` bertambah bila dokumen diunggah ulang di luar siklus revisi resmi.
 * Ref: Tahap 4 §3.5
 */
class DokumenPermohonan extends Model
{
    use HasFactory;

    protected $table = 'dokumen_permohonan';
    public const UPDATED_AT = null;

    public const JENIS_SURAT_PERMOHONAN = 'surat_permohonan';
    public const JENIS_SURAT_PERNYATAAN = 'surat_pernyataan';
    public const JENIS_RANCANGAN_DENAH = 'rancangan_denah';
    public const JENIS_IZIN_PBF = 'izin_pbf';
    public const JENIS_STRA_PJ = 'stra_pj';

    /** Ekstensi & ukuran maksimum (KB) yang diterima untuk seluruh jenis dokumen. */
    public const EKSTENSI_DIIZINKAN = ['pdf', 'jpg', 'jpeg', 'png'];
    public const UKURAN_MAKS_KB = 10240;

    /**
     * Metadata 5 dokumen wajib permohonan — SATU sumber kebenaran yang dipakai
     * bersama oleh form (label & urutan), FormRequest (aturan validasi), dan
     * controller (pemetaan field -> jenis_dokumen). Menambah/mengubah dokumen
     * cukup di sini.
     */
    public const JENIS = [
        self::JENIS_SURAT_PERMOHONAN => [
            'label' => 'Surat Permohonan bermaterai',
            'keterangan' => 'Ditandatangani direktur & bermaterai Rp10.000',
        ],
        self::JENIS_SURAT_PERNYATAAN => [
            'label' => 'Surat Pernyataan',
            'keterangan' => 'Pernyataan kebenaran data yang diajukan',
        ],
        self::JENIS_RANCANGAN_DENAH => [
            'label' => 'Rancangan Denah PBF',
            'keterangan' => 'Denah berskala lengkap dengan keterangan ruang',
        ],
        self::JENIS_IZIN_PBF => [
            'label' => 'Izin PBF (NIE)',
            'keterangan' => 'Izin PBF yang masih berlaku',
        ],
        self::JENIS_STRA_PJ => [
            'label' => 'STRA / SIK Penanggung Jawab',
            'keterangan' => 'Surat Tanda Registrasi / Izin Kerja Apoteker PJ',
        ],
    ];

    /** Aturan validasi Laravel untuk kelima input dokumen. */
    public static function aturanValidasi(bool $wajib = true): array
    {
        $aturan = sprintf(
            '%s|file|mimes:%s|max:%d',
            $wajib ? 'required' : 'nullable',
            implode(',', self::EKSTENSI_DIIZINKAN),
            self::UKURAN_MAKS_KB
        );

        return array_fill_keys(array_keys(self::JENIS), $aturan);
    }

    protected $fillable = [
        'permohonan_id',
        'jenis_dokumen',
        'versi',
        'path_file',
        'nama_file_asli',
        'ukuran_file_kb',
        'mime_type',
        'checksum',
        'uploaded_by_user_id',
        'uploaded_by_pemohon_id',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    /** Label ramah-baca dokumen (mis. "STRA / SIK Penanggung Jawab"). */
    public function getLabelAttribute(): string
    {
        return self::JENIS[$this->jenis_dokumen]['label']
            ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $this->jenis_dokumen));
    }
}
