<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDistribusiRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'staff_id' => 'required|exists:users,id',
            'catatan' => 'nullable|string',
        ];
    }
}
