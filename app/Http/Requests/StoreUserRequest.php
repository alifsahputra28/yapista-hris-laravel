<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $email = Str::lower(trim((string) $this->input('email')));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => $email !== '' ? $email : null,
            'employee_id' => $this->filled('employee_id') ? $this->input('employee_id') : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(User::ROLES)],
            'employee_id' => [
                'nullable',
                'required_if:role,pegawai',
                Rule::exists('employees', 'id')->where(
                    fn (Builder $query): Builder => $query->whereNull('user_id')
                ),
            ],
            'password' => ['required', 'confirmed', Password::min(12)],
            'status' => ['required', Rule::in(User::STATUSES)],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('email')) {
                return;
            }

            if (filled($this->input('email')) && User::query()->whereRaw('LOWER(email) = ?', [$this->string('email')->toString()])->exists()) {
                $validator->errors()->add('email', 'Email sudah digunakan.');
            }

            $employee = $this->input('employee_id') ? \App\Models\Employee::find($this->input('employee_id')) : null;
            if (blank($this->input('email')) && (! $employee || ! $employee->hasValidEmployeeNumber())) {
                $validator->errors()->add('email', 'Email wajib diisi jika pegawai belum memiliki NUP yang valid.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'employee_id.required_if' => 'Pegawai terkait wajib dipilih untuk role Pegawai.',
            'employee_id.exists' => 'Pegawai tidak tersedia atau sudah terhubung ke akun lain.',
            'password.min' => 'Password minimal harus terdiri dari 12 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'status.in' => 'Status yang dipilih tidak valid.',
        ];
    }
}
