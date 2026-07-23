<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Entitas sentral aplikasi. Merepresentasikan satu pengajuan Surat Pengesahan Denah PBF.
 *
 * ATURAN BISNIS KRITIS (lihat CLAUDE.md §3) — JANGAN diubah lewat query langsung,
 * selalu lewat StatusTransitionService:
 * - 1 permohonan = 1 Staff aktif (lihat Distribusi::aktif()).
 * - Maks. 3 siklus revisi, siklus ke-4 tidak boleh terjadi.
 * - Status akhir sukses = STATUS_TERBIT_SURAT_PENGESAHAN (bukan "Selesai").
 * - Pengajuan pertama hanya oleh Kepala Balai (dibuat_oleh_tipe = kepala_balai).
 * - Pengajuan ulang dilakukan mandiri oleh Pemohon (dibuat_oleh_tipe = pemohon),
 *   ditautkan lewat parent_permohonan_id dan WAJIB ditampilkan eksplisit ke pemohon.
 *
 * Ref: DESIGN.md §3 & §6, Tahap 2, Tahap 4 §3.4
 */
class Permohonan extends Model
{
    use HasFactory;

    protected $table = 'permohonan';

    // 9 status baku (Tahap 2 v1.3)
    public const STATUS_PENGAJUAN = 'pengajuan';
    public const STATUS_DIDISPOSISIKAN = 'didisposisikan';
    public const STATUS_PROSES_EVALUASI = 'proses_evaluasi';
    public const STATUS_REVISI_1 = 'revisi_1';
    public const STATUS_REVISI_2 = 'revisi_2';
    public const STATUS_REVISI_3 = 'revisi_3';
    public const STATUS_DITUTUP_PENGAJUAN_ULANG = 'ditutup_pengajuan_ulang';
    public const STATUS_MENUNGGU_SURAT_PENGESAHAN = 'menunggu_surat_pengesahan';
    public const STATUS_TERBIT_SURAT_PENGESAHAN = 'terbit_surat_pengesahan';

    public const DIBUAT_OLEH_KEPALA_BALAI = 'kepala_balai';
    public const DIBUAT_OLEH_PEMOHON = 'pemohon';

    protected $fillable = [
        'no_registrasi',
        'pbf_id',
        'parent_permohonan_id',
        'nama_pbf_snapshot',
        'nib_snapshot',
        'email_snapshot',
        'no_wa_snapshot',
        'status_saat_ini',
        'revisi_ke',
        'tanggal_pengajuan',
        'sla_deadline_current',
        'is_overdue',
        'kepala_balai_id',
        'dibuat_oleh_tipe',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'datetime',
    ];

    // ── Relasi Dasar ──────────────────────────────────

    public function pbf(): BelongsTo
    {
        return $this->belongsTo(Pbf::class);
    }

    public function kepalaBalai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepala_balai_id');
    }

    /** Permohonan asal jika ini adalah hasil pengajuan ulang. */
    public function pengajuanAsal(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'parent_permohonan_id');
    }

    /** Daftar permohonan baru yang merupakan pengajuan ulang dari permohonan ini. */
    public function pengajuanUlang(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'parent_permohonan_id');
    }

    // ── Dokumen ───────────────────────────────────────

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPermohonan::class);
    }

    // ── Disposisi & Distribusi ────────────────────────

    public function disposisi(): HasOne
    {
        return $this->hasOne(Disposisi::class);
    }

    public function distribusi(): HasMany
    {
        return $this->hasMany(Distribusi::class);
    }

    /** Distribusi yang sedang berlaku (staff penanggung jawab saat ini). */
    public function distribusiAktif(): HasOne
    {
        return $this->hasOne(Distribusi::class)->where('is_aktif', true);
    }

    // ── Evaluasi & Revisi ─────────────────────────────

    public function evaluasi(): HasMany
    {
        return $this->hasMany(Evaluasi::class)->orderBy('siklus_ke');
    }

    public function evaluasiTerakhir(): HasOne
    {
        return $this->hasOne(Evaluasi::class)->latestOfMany('siklus_ke');
    }

    // ── Penerbitan ────────────────────────────────────

    public function suratPengesahan(): HasOne
    {
        return $this->hasOne(SuratPengesahan::class);
    }

    // ── Timeline, Notifikasi, Eskalasi ────────────────

    public function statusLog(): HasMany
    {
        return $this->hasMany(StatusLog::class)->orderBy('waktu_mulai');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    public function reassignmentLog(): HasMany
    {
        return $this->hasMany(ReassignmentLog::class);
    }

    // ── Helper ────────────────────────────────────────

    public function isPengajuanUlang(): bool
    {
        return $this->parent_permohonan_id !== null;
    }

    public function isStatusAkhir(): bool
    {
        return in_array($this->status_saat_ini, [
            self::STATUS_TERBIT_SURAT_PENGESAHAN,
            self::STATUS_DITUTUP_PENGAJUAN_ULANG,
        ], true);
    }
}
