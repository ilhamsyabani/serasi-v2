<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusMaster extends Model
{
    use HasFactory;

    protected $table = 'status_master';

    protected $fillable = [
        'kode',
        'nama',
        'urutan',
        'is_final',
        'is_clockoff',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'is_clockoff' => 'boolean',
    ];
}
