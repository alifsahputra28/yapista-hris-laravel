<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:2048',
                'extensions:xlsx',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'File Excel wajib dipilih.',
            'file.file' => 'File import tidak valid.',
            'file.extensions' => 'Format file harus XLSX.',
            'file.mimetypes' => 'Isi file tidak sesuai dengan format XLSX.',
            'file.max' => 'Ukuran file import maksimal 2 MB.',
        ];
    }
}
