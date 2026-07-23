<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenRevisi extends Model
{
    use HasFactory;

    protected $table = 'dokumen_revisi';

    protected $fillable = [
        'revisi_id',
        'jenis_dokumen',
        'nama_file_asli',
        'path_file',
        'ukuran_file_kb',
        'mime_type',
        'checksum',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function revisi(): BelongsTo
    {
        return $this->belongsTo(Revisi::class);
    }
}
