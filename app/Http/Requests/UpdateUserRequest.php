<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nip' => 'required|string|max:30|unique:users,nip,' . $this->user->id,
            'nama' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'role_id' => 'required|exists:roles,id',
            'is_aktif' => 'required|boolean',
            'password' => 'nullable|string|min:8',
        ];
    }
}
