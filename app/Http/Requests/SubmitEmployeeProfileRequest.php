<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitEmployeeProfileRequest extends FormRequest
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
            'declaration' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'declaration.accepted' => 'Pernyataan kebenaran data dan dokumen harus disetujui.',
        ];
    }
}
