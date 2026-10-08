<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class CreateEmployeeAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $email = Str::lower(trim((string) $this->input('email')));

        $this->merge([
            'email' => $email !== '' ? $email : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['required', 'confirmed', Password::min(12)],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $employee = $this->route('employee');

            if (! $employee instanceof Employee) {
                return;
            }

            if ($employee->user_id !== null) {
                $validator->errors()->add('employee', 'Pegawai sudah terhubung ke akun lain.');
            }

            if (blank($this->input('email')) && ! $employee->hasValidEmployeeNumber()) {
                $validator->errors()->add('email', 'Email Login wajib diisi jika NUP belum valid.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah digunakan.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'password.min' => 'Password minimal harus terdiri dari 12 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
