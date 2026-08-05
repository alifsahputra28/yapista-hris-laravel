<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeFamilyMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeFamilyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPegawai() === true
            && $this->user()->employee instanceof Employee;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', Rule::in(array_keys(EmployeeFamilyMember::RELATIONSHIPS))],
            'nik' => ['nullable', 'digits:16'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'occupation' => ['nullable', 'string', 'max:150'],
            'is_dependent' => ['nullable', 'boolean'],
            'bpjs_status' => ['nullable', Rule::in(array_keys(EmployeeFamilyMember::BPJS_STATUSES))],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap anggota keluarga wajib diisi.',
            'relationship.required' => 'Hubungan keluarga wajib dipilih.',
            'relationship.in' => 'Hubungan keluarga tidak valid.',
            'nik.digits' => 'NIK anggota keluarga harus terdiri dari 16 digit angka.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'bpjs_status.in' => 'Status BPJS tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['full_name', 'relationship', 'nik', 'birth_place', 'occupation', 'bpjs_status'] as $field) {
            if (! $this->exists($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = trim($this->string($field)->toString());
            $normalized[$field] = $value === '' ? null : $value;
        }

        $this->merge($normalized);
    }
}
