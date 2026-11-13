<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entitas sentral aplikasi. Merepresentasikan satu pengajuan Surat Pengesahan Denah PBF.
 *
 * ATURAN BISNIS KRITIS (lihat CLAUDE.md §3) — JANGAN diubah lewat query langsung,
 * selalu lewat StatusTransitionService:
 * - 1 permohonan = 1 Staff aktif (lihat Distribusi::aktif()).
 * - Maks. 4 siklus revisi (Permohonan::MAKS_REVISI), siklus ke-5 tidak boleh terjadi.
 * - Status akhir sukses = STATUS_TERBIT_SURAT_PENGESAHAN (bukan "Selesai").
 * - Pengajuan pertama hanya oleh Kepala Balai (dibuat_oleh_tipe = kepala_balai).
 * - Pengajuan ulang dilakukan mandiri oleh Pemohon (dibuat_oleh_tipe = pemohon),
 *   ditautkan lewat parent_permohonan_id dan WAJIB ditampilkan eksplisit ke pemohon.
 *
 * Ref: DESIGN.md §3 & §6, Tahap 2, Tahap 4 §3.4
 */
class Permohonan extends Model
{
    // Hapus = soft delete: baris, riwayat status, dan audit trail tetap tersimpan
    // (CLAUDE.md §4 — riwayat instansi pemerintah tidak boleh hilang).
    use HasFactory, SoftDeletes;

    protected $table = 'permohonan';

    // 9 status baku (Tahap 2 v1.3)
    public const STATUS_PENGAJUAN = 'pengajuan';
    public const STATUS_DIDISPOSISIKAN = 'didisposisikan';
    public const STATUS_PROSES_EVALUASI = 'proses_evaluasi';
    public const STATUS_REVISI_1 = 'revisi_1';
    public const STATUS_REVISI_2 = 'revisi_2';
    public const STATUS_REVISI_3 = 'revisi_3';
    public const STATUS_REVISI_4 = 'revisi_4';
    public const STATUS_DITUTUP_PENGAJUAN_ULANG = 'ditutup_pengajuan_ulang';
    public const STATUS_MENUNGGU_SURAT_PENGESAHAN = 'menunggu_surat_pengesahan';
    public const STATUS_TERBIT_SURAT_PENGESAHAN = 'terbit_surat_pengesahan';

    /**
     * Batas jumlah siklus revisi (CLAUDE.md §3 poin 2). Setelah revisi ke-MAKS
     * masih Tidak Lengkap, permohonan ditutup (ditutup_pengajuan_ulang).
     * Bila diubah, tambahkan juga konstanta STATUS_REVISI_N + baris status_master/sla_config.
     */
    public const MAKS_REVISI = 4;

    /** Semua status tahap revisi (clock-off, menunggu upload pemohon). */
    public const STATUS_REVISI = [
        self::STATUS_REVISI_1,
        self::STATUS_REVISI_2,
        self::STATUS_REVISI_3,
        self::STATUS_REVISI_4,
    ];

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
        'sla_deadline_current' => 'date',
        'is_overdue' => 'boolean',
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

    public function revisi(): HasMany
    {
        return $this->hasMany(Revisi::class);
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

    // ── Scope ─────────────────────────────────────────

    /**
     * Permohonan yang menjadi urusan seorang Kepala Balai: yang ia buat sendiri,
     * ditambah pengajuan ulang mandiri pemohon (`kepala_balai_id` NULL, CLAUDE.md §3 poin 6).
     */
    public function scopeUntukKepalaBalai($query, int $userId)
    {
        return $query->where(fn ($q) => $q
            ->where('permohonan.kepala_balai_id', $userId)
            ->orWhereNull('permohonan.kepala_balai_id'));
    }

    /** Filter tabel permohonan: search, status, tanggal_dari, tanggal_sampai. */
    public function scopeFilterTabel(Builder $query, array $filter): Builder
    {
        return $query
            ->when($filter['search'] ?? null, fn ($q, $search) => $q->where(fn ($w) => $w
                ->where('nama_pbf_snapshot', 'like', "%{$search}%")
                ->orWhere('no_registrasi', 'like', "%{$search}%")
                ->orWhere('nib_snapshot', 'like', "%{$search}%")))
            ->when($filter['status'] ?? null, fn ($q, $status) => $q->where('status_saat_ini', $status))
            ->when($filter['tanggal_dari'] ?? null, fn ($q, $tgl) => $q->whereDate('tanggal_pengajuan', '>=', $tgl))
            ->when($filter['tanggal_sampai'] ?? null, fn ($q, $tgl) => $q->whereDate('tanggal_pengajuan', '<=', $tgl));
    }

    // ── Helper ────────────────────────────────────────

    public function isPengajuanUlang(): bool
    {
        return $this->parent_permohonan_id !== null;
    }

    public static function statusList(): array
    {
        return [
            self::STATUS_PENGAJUAN => 'Pengajuan',
            self::STATUS_DIDISPOSISIKAN => 'Didisposisikan',
            self::STATUS_PROSES_EVALUASI => 'Proses Evaluasi',
            self::STATUS_REVISI_1 => 'Revisi 1',
            self::STATUS_REVISI_2 => 'Revisi 2',
            self::STATUS_REVISI_3 => 'Revisi 3',
            self::STATUS_REVISI_4 => 'Revisi 4',
            self::STATUS_DITUTUP_PENGAJUAN_ULANG => 'Ditutup – Pengajuan Ulang',
            self::STATUS_MENUNGGU_SURAT_PENGESAHAN => 'Menunggu Surat Pengesahan',
            self::STATUS_TERBIT_SURAT_PENGESAHAN => 'Terbit Surat Pengesahan',
        ];
    }

    public function isStatusRevisi(): bool
    {
        return in_array($this->status_saat_ini, self::STATUS_REVISI, true);
    }

    /** Siklus revisi yang sedang berjalan adalah kesempatan terakhir. */
    public function isRevisiTerakhir(): bool
    {
        return (int) $this->revisi_ke >= self::MAKS_REVISI;
    }

    public function isStatusAkhir(): bool
    {
        return in_array($this->status_saat_ini, [
            self::STATUS_TERBIT_SURAT_PENGESAHAN,
            self::STATUS_DITUTUP_PENGAJUAN_ULANG,
        ], true);
    }

    /**
     * Generate nomor registrasi unik下一个. Format: PBF/DENAH/{tahun}/{urutan 5 digit}.
     *urutan dihitung berdasarkan MAX untuk tahun berjalan, jadi aman meski ada record dihapus.
     */
    public static function generateNoRegistrasi(): string
    {
        $tahun = date('Y');
        $prefix = "PBF/DENAH/{$tahun}/";

        // withTrashed: nomor milik permohonan yang dihapus tidak boleh dipakai ulang.
        $last = static::withTrashed()->where('no_registrasi', 'LIKE', "{$prefix}%")
            ->orderByDesc('no_registrasi')
            ->value('no_registrasi');

        $next = 1;
        if ($last && preg_match('/(\d{5})$/', $last, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
