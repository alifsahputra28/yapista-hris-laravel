<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeCertificationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'certificate_number' => ['nullable', 'string', 'max:150'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'competency_field' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'expired_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['issued_at', 'expired_at'])) {
                return;
            }

            if ($this->filled(['issued_at', 'expired_at'])
                && $this->date('expired_at')->isBefore($this->date('issued_at'))) {
                $validator->errors()->add('expired_at', 'Tanggal kedaluwarsa tidak boleh sebelum tanggal terbit.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama sertifikasi wajib diisi.',
            'issued_at.before_or_equal' => 'Tanggal terbit tidak boleh melewati hari ini.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['name', 'certificate_number', 'issuer', 'competency_field'] as $field) {
            if (! $this->exists($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = trim($this->string($field)->toString());
            $normalized[$field] = $value === '' ? null : $value;
        }

        $this->merge($normalized);
    }
}
