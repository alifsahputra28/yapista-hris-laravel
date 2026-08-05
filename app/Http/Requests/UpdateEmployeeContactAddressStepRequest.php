<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeContactAddressStepRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+62|62|0)8[1-9][0-9]{6,12}$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+62|62|0)8[1-9][0-9]{6,12}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'identity_address' => ['nullable', 'string', 'max:2000'],
            'domicile_same_as_identity' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:2000'],
            'domicile_province' => ['nullable', 'string', 'max:100'],
            'domicile_city' => ['nullable', 'string', 'max:100'],
            'domicile_district' => ['nullable', 'string', 'max:100'],
            'domicile_village' => ['nullable', 'string', 'max:100'],
            'domicile_postal_code' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'wizard_action' => ['required', Rule::in(['stay', 'next'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['phone', 'whatsapp_number', 'email', 'identity_address', 'address', 'domicile_province', 'domicile_city', 'domicile_district', 'domicile_village', 'domicile_postal_code'] as $field) {
            if ($this->exists($field) && is_string($this->input($field))) {
                $value = trim($this->string($field)->toString());
                $normalized[$field] = $value === '' ? null : $value;
            }
        }
        $this->merge($normalized);
    }
}
