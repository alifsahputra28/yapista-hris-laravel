<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeEducation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeEducationRequest extends FormRequest
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
        $currentYear = (int) now()->format('Y');

        return [
            'education_level' => ['required', 'string', Rule::in(array_keys(EmployeeEducation::EDUCATION_LEVELS))],
            'institution_name' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'start_year' => ['nullable', 'integer', 'digits:4', 'min:1950', 'max:'.$currentYear],
            'graduation_year' => ['nullable', 'integer', 'digits:4', 'min:1950', 'max:'.$currentYear],
            'certificate_number' => ['nullable', 'string', 'max:150'],
            'degree_prefix' => ['nullable', 'string', 'max:50'],
            'degree_suffix' => ['nullable', 'string', 'max:100'],
            'is_highest' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled(['start_year', 'graduation_year'])
                && (int) $this->input('graduation_year') < (int) $this->input('start_year')) {
                $validator->errors()->add('graduation_year', 'Tahun lulus tidak boleh lebih kecil dari tahun masuk.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'education_level.required' => 'Jenjang pendidikan wajib dipilih.',
            'education_level.in' => 'Jenjang pendidikan tidak valid.',
            'institution_name.required' => 'Nama institusi pendidikan wajib diisi.',
            'start_year.digits' => 'Tahun masuk harus terdiri dari 4 digit.',
            'graduation_year.digits' => 'Tahun lulus harus terdiri dari 4 digit.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['education_level', 'institution_name', 'major', 'start_year', 'graduation_year', 'certificate_number', 'degree_prefix', 'degree_suffix'] as $field) {
            if (! $this->exists($field) || ! is_string($this->input($field))) {
                continue;
            }

            $value = trim($this->string($field)->toString());
            $normalized[$field] = $value === '' ? null : $value;
        }

        $this->merge($normalized);
    }
}
