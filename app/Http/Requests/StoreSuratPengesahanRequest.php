<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratPengesahanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'file_surat' => 'required|file|mimes:pdf|max:10240',
            'nomor_surat' => 'required|string|max:100',
        ];
    }
}
