<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasVersion4Uuids;

/**
 * UUID sebagai pengenal PUBLIK (di URL), menggantikan ID angka berurutan yang
 * mudah ditebak/dihitung (enumerasi). Primary key `id` (integer) & seluruh
 * foreign key TIDAK berubah — UUID disimpan di kolom terpisah `uuid`.
 *
 * - UUID v4 (acak penuh), bukan v7, agar tidak membocorkan waktu pembuatan.
 * - Dibuat otomatis saat model di-create.
 * - Route model binding memakai `uuid`; nilai yang bukan UUID valid langsung 404.
 *
 * Hak akses TETAP dicek oleh Policy/controller — UUID bukan pengganti otorisasi.
 */
trait HasPublicUuid
{
    use HasVersion4Uuids;

    /** Hanya kolom `uuid` yang diisi UUID; primary key tetap auto-increment. */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
