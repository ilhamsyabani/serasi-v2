<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PemohonLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'identifier' => 'required|string',
            'password' => 'required|string',
        ];
    }
}
