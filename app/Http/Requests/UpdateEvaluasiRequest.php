<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluasiRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hasil' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ];
    }
}
