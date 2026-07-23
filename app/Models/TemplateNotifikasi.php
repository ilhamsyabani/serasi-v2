<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateNotifikasi extends Model
{
    use HasFactory;

    protected $table = 'template_notifikasi';

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
