<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Rules\UniqueEmployeeNik;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeProfileRequest extends FormRequest
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
        $employeeId = $this->user()?->employee?->getKey();

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'nik' => ['nullable', 'digits:16', new UniqueEmployeeNik($employeeId)],
            'family_card_number' => ['nullable', 'digits:16'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'religion' => ['nullable', Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+62|62|0)8[1-9][0-9]{6,12}$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+62|62|0)8[1-9][0-9]{6,12}$/'],
            'identity_address' => ['nullable', 'string', 'max:2000'],
            'domicile_same_as_identity' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:2000'],
            'domicile_province' => ['nullable', 'string', 'max:100'],
            'domicile_city' => ['nullable', 'string', 'max:100'],
            'domicile_district' => ['nullable', 'string', 'max:100'],
            'domicile_village' => ['nullable', 'string', 'max:100'],
            'domicile_postal_code' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:\+62|62|0)8[1-9][0-9]{6,12}$/'],
            'emergency_contact_address' => ['nullable', 'string', 'max:2000'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'family_card_number.digits' => 'Nomor Kartu Keluarga harus terdiri dari 16 digit angka.',
            'phone.regex' => 'Format nomor HP tidak valid. Gunakan awalan +62, 62, atau 08.',
            'whatsapp_number.regex' => 'Format nomor WhatsApp tidak valid. Gunakan awalan +62, 62, atau 08.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'domicile_postal_code.regex' => 'Kode pos harus terdiri dari 5 digit angka.',
            'emergency_contact_phone.regex' => 'Format nomor kontak darurat tidak valid. Gunakan awalan +62, 62, atau 08.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        $fields = [
            'full_name',
            'email',
            'nik',
            'family_card_number',
            'birth_place',
            'nationality',
            'phone',
            'whatsapp_number',
            'identity_address',
            'address',
            'domicile_province',
            'domicile_city',
            'domicile_district',
            'domicile_village',
            'domicile_postal_code',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_phone',
            'emergency_contact_address',
        ];

        foreach ($fields as $field) {
            if (! $this->exists($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = trim($this->string($field)->toString());
            $normalized[$field] = $value === '' ? null : $value;
        }

        $this->merge($normalized);
    }
}
