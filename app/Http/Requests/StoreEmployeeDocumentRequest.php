<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Support\Documents\EmployeeDocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPegawai() === true
            && $this->user()->employee instanceof Employee;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(array_keys(EmployeeDocumentType::employeeUploadable()))],
            'employee_education_id' => ['nullable', 'integer', 'exists:employee_educations,id'],
            'employee_certification_id' => ['nullable', 'integer', 'exists:employee_certifications,id'],
            'document_context' => ['nullable', Rule::in(['documents', 'wizard'])],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.in' => 'Jenis dokumen tidak dapat diunggah oleh pegawai.',
            'file.mimes' => 'Dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'file.mimetypes' => 'Isi file dokumen tidak sesuai format yang diizinkan.',
            'file.max' => 'Ukuran dokumen maksimal 5 MB.',
        ];
    }
}
