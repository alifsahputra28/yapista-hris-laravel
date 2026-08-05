<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeAdministrativeDetail;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeAdministrativeDetailRequest extends FormRequest
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
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'regex:/^\d{6,30}$/'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'tax_status' => ['nullable', Rule::in(array_keys(EmployeeAdministrativeDetail::TAX_STATUSES))],
            'tax_identification_number' => ['nullable', 'string', 'regex:/^(?:\d{15}|\d{16})$/'],
            'nik_used_as_tax_id' => ['nullable', 'boolean'],
            'ptkp_status' => ['nullable', Rule::in(EmployeeAdministrativeDetail::PTKP_STATUSES)],
            'bpjs_health_status' => ['nullable', Rule::in(array_keys(EmployeeAdministrativeDetail::BPJS_STATUSES))],
            'bpjs_health_number' => ['nullable', 'string', 'regex:/^\d{8,20}$/'],
            'bpjs_employment_status' => ['nullable', Rule::in(array_keys(EmployeeAdministrativeDetail::BPJS_STATUSES))],
            'bpjs_employment_number' => ['nullable', 'string', 'regex:/^\d{8,20}$/'],
            'wizard_action' => ['nullable', Rule::in(['stay', 'next'])],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_number.regex' => 'Nomor rekening harus terdiri dari 6 sampai 30 digit angka.',
            'tax_identification_number.regex' => 'Nomor identitas pajak harus terdiri dari 15 atau 16 digit angka.',
            'tax_status.in' => 'Status pajak tidak valid.',
            'ptkp_status.in' => 'Status PTKP tidak valid.',
            'bpjs_health_status.in' => 'Status BPJS Kesehatan tidak valid.',
            'bpjs_health_number.regex' => 'Nomor BPJS Kesehatan harus terdiri dari 8 sampai 20 digit angka.',
            'bpjs_employment_status.in' => 'Status BPJS Ketenagakerjaan tidak valid.',
            'bpjs_employment_number.regex' => 'Nomor BPJS Ketenagakerjaan harus terdiri dari 8 sampai 20 digit angka.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        $stringFields = [
            'bank_name',
            'bank_account_holder',
            'tax_status',
            'ptkp_status',
            'bpjs_health_status',
            'bpjs_employment_status',
        ];

        foreach ($stringFields as $field) {
            if (! $this->exists($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = trim($this->string($field)->toString());
            $normalized[$field] = $value === '' ? null : $value;
        }

        foreach (['bank_account_number', 'bpjs_health_number', 'bpjs_employment_number'] as $field) {
            if (! $this->exists($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = preg_replace('/\s+/', '', trim($this->string($field)->toString()));
            $normalized[$field] = $value === '' ? null : $value;
        }

        if ($this->exists('tax_identification_number') && is_string($this->input('tax_identification_number'))) {
            $value = preg_replace('/[\s.\-]+/', '', trim($this->string('tax_identification_number')->toString()));
            $normalized['tax_identification_number'] = $value === '' ? null : $value;
        }

        if ($this->exists('nik_used_as_tax_id') && $this->input('nik_used_as_tax_id') === '') {
            $normalized['nik_used_as_tax_id'] = null;
        }

        $this->merge($normalized);
    }
}
