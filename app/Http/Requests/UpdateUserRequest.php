<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'employee_id' => $this->filled('employee_id') ? $this->input('employee_id') : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($managedUser),
            ],
            'role' => ['required', Rule::in(User::ROLES)],
            'employee_id' => [
                'nullable',
                'required_if:role,pegawai',
                Rule::exists('employees', 'id')->where(function (Builder $query) use ($managedUser): Builder {
                    return $query->where(function (Builder $query) use ($managedUser): void {
                        $query->whereNull('user_id')->orWhere('user_id', $managedUser?->id);
                    });
                }),
            ],
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

            $managedUser = $this->route('user');
            $duplicate = User::query()
                ->whereRaw('LOWER(email) = ?', [$this->string('email')->toString()])
                ->when($managedUser, fn ($query) => $query->whereKeyNot($managedUser->id))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('email', 'Email sudah digunakan.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'employee_id.required_if' => 'Pegawai terkait wajib dipilih untuk role Pegawai.',
            'employee_id.exists' => 'Pegawai tidak tersedia atau sudah terhubung ke akun lain.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'status.in' => 'Status yang dipilih tidak valid.',
        ];
    }
}
