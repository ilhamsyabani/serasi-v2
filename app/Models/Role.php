<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role untuk User internal BBPOM.
 * Kode baku: kepala_balai, ketua_tim, staff_sertifikasi, admin_it
 * Ref: DESIGN.md §2, Tahap 4 §3.1
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    public const KEPALA_BALAI = 'kepala_balai';
    public const KETUA_TIM = 'ketua_tim';
    public const STAFF_SERTIFIKASI = 'staff_sertifikasi';
    public const ADMIN_IT = 'admin_it';

    protected $fillable = [
        'kode',
        'nama',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
