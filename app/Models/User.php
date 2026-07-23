<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * User internal BBPOM: Kepala Balai, Ketua Tim Sertifikasi, Staff Sertifikasi, Admin IT.
 * Autentikasi via SSO BPOM — guard terpisah dari Pbf (jangan disatukan dengan guard default).
 * Ref: DESIGN.md §2, Tahap 4 §3.2
 */
class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'role_id',
        'nip',
        'nama',
        'email',
        'sso_identifier',
        'password',
        'is_aktif',
    ];

    protected $hidden = [
        'sso_identifier',
    ];

    protected $casts = [
        'is_aktif' => 'boolean',
    ];

    // ── Role ──────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isKepalaBalai(): bool
    {
        return $this->role?->kode === Role::KEPALA_BALAI;
    }

    public function isKetuaTim(): bool
    {
        return $this->role?->kode === Role::KETUA_TIM;
    }

    public function isStaffSertifikasi(): bool
    {
        return $this->role?->kode === Role::STAFF_SERTIFIKASI;
    }

    public function isAdminIt(): bool
    {
        return $this->role?->kode === Role::ADMIN_IT;
    }

    // ── Sebagai Kepala Balai ──────────────────────────

    public function permohonanDiinput(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'kepala_balai_id');
    }

    public function disposisiSebagaiKepalaBalai(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'kepala_balai_id');
    }

    // ── Sebagai Ketua Tim ─────────────────────────────

    public function disposisiSebagaiKetuaTim(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'ketua_tim_id');
    }

    public function distribusiSebagaiKetuaTim(): HasMany
    {
        return $this->hasMany(Distribusi::class, 'ketua_tim_id');
    }

    public function reassignmentSebagaiKetuaTim(): HasMany
    {
        return $this->hasMany(ReassignmentLog::class, 'ketua_tim_id');
    }

    // ── Sebagai Staff Sertifikasi ─────────────────────

    public function distribusiSebagaiStaff(): HasMany
    {
        return $this->hasMany(Distribusi::class, 'staff_id');
    }

    public function evaluasiDiisi(): HasMany
    {
        return $this->hasMany(Evaluasi::class, 'staff_id');
    }

    public function suratPengesahanDiupload(): HasMany
    {
        return $this->hasMany(SuratPengesahan::class, 'staff_id');
    }

    public function reassignmentSebagaiStaffLama(): HasMany
    {
        return $this->hasMany(ReassignmentLog::class, 'staff_lama_id');
    }

    public function reassignmentSebagaiStaffBaru(): HasMany
    {
        return $this->hasMany(ReassignmentLog::class, 'staff_baru_id');
    }
}
