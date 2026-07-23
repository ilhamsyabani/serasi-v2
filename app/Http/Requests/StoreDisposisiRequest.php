<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisposisiRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ketua_tim_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string',
        ];
    }
}
