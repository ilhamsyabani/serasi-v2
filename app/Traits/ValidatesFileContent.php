<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use finfo;

trait ValidatesFileContent
{
    protected function assertAllowedFileMime(UploadedFile $file, array $allowed = ['application/pdf','image/jpeg','image/png','image/jpg']): void
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());

        if (!in_array($mime, $allowed, true)) {
            abort(422, 'Format berkas tidak diizinkan: ' . $mime);
        }
    }
}
