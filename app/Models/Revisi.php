<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Revisi extends Model
{
    use HasFactory;

    protected $table = 'revisi';

    protected $fillable = [
        'evaluasi_id',
        'permohonan_id',
    ];

    public function evaluasi(): BelongsTo
    {
        return $this->belongsTo(Evaluasi::class);
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function dokumenRevisi(): HasMany
    {
        return $this->hasMany(DokumenRevisi::class);
    }
}
