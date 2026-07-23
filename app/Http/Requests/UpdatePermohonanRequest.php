<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermohonanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nama_pbf_snapshot' => 'required|string|max:200',
            'email_snapshot' => 'required|email|max:150',
            'no_wa_snapshot' => 'required|string|max:20',
        ];
    }
}
