<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeEmergencyContactStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPegawai() === true && $this->user()->employee instanceof Employee;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+62|62|0)8[1-9][0-9]{6,12}$/'],
            'emergency_contact_address' => ['nullable', 'string', 'max:2000'],
            'wizard_action' => ['required', Rule::in(['stay', 'next'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone', 'emergency_contact_address'] as $field) {
            if ($this->exists($field) && is_string($this->input($field))) {
                $value = trim($this->string($field)->toString());
                $normalized[$field] = $value === '' ? null : $value;
            }
        }
        $this->merge($normalized);
    }
}
