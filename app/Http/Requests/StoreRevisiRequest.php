<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRevisiRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'dokumen.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}
