<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Rules\UniqueEmployeeNik;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeIdentificationStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPegawai() === true && $this->user()->employee instanceof Employee;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $employeeId = $this->user()?->employee?->getKey();

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'digits:16', new UniqueEmployeeNik($employeeId)],
            'family_card_number' => ['nullable', 'digits:16'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'religion' => ['nullable', Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
            ],
            'wizard_action' => ['required', Rule::in(['stay', 'next'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'family_card_number.digits' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit angka.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['full_name', 'nik', 'family_card_number', 'birth_place', 'nationality'] as $field) {
            if ($this->exists($field) && is_string($this->input($field))) {
                $value = trim($this->string($field)->toString());
                $normalized[$field] = $value === '' ? null : $value;
            }
        }
        $this->merge($normalized);
    }
}
