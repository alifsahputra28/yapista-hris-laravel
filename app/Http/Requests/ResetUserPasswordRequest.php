<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(12)],
            'reset_user_id' => ['nullable', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.min' => 'Password baru minimal harus terdiri dari 12 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}
