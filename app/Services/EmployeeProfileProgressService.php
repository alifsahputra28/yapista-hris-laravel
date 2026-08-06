<?php

namespace App\Services;

use App\Models\Employee;
class EmployeeProfileProgressService
{
    /**
     * @return array{
     *   sections: array<string, array{label: string, completed: bool, percentage: int, completed_items: int, total_items: int, missing: array<int, string>}>,
     *   completed_sections: int,
     *   total_sections: int,
     *   percentage: int,
     *   next_incomplete_step: string
     * }
     */
    public function calculate(Employee $employee): array
    {
        $employee->loadMissing(['familyMembers', 'educations', 'administrativeDetail']);

        $sections = [
            'identification' => $this->identification($employee),
            'contact-address' => $this->contactAddress($employee),
            'family' => $this->family($employee),
            'education' => $this->education($employee),
            'administration' => $this->administration($employee),
        ];
        $completedSections = collect($sections)->where('completed', true)->count();
        $nextIncomplete = collect($sections)->search(fn (array $section): bool => ! $section['completed']);

        return [
            'sections' => $sections,
            'completed_sections' => $completedSections,
            'total_sections' => count($sections),
            'percentage' => (int) round(collect($sections)->avg('percentage')),
            'next_incomplete_step' => is_string($nextIncomplete) ? $nextIncomplete : 'review',
        ];
    }

    private function identification(Employee $employee): array
    {
        return $this->section('Identitas Pribadi', [
            'Nama lengkap' => filled($employee->full_name),
            'NIK' => filled($employee->nik),
            'Nomor Kartu Keluarga' => filled($employee->family_card_number),
            'Tempat lahir' => filled($employee->birth_place),
            'Tanggal lahir' => filled($employee->birth_date),
            'Jenis kelamin' => filled($employee->gender),
            'Agama' => filled($employee->religion),
            'Status perkawinan' => filled($employee->marital_status),
            'Kewarganegaraan' => filled($employee->nationality),
            'Pas foto' => filled($employee->photo),
        ]);
    }

    private function contactAddress(Employee $employee): array
    {
        return $this->section('Kontak & Alamat', [
            'Nomor HP' => filled($employee->phone),
            'Nomor WhatsApp' => filled($employee->whatsapp_number),
            'Email pribadi' => filled($employee->email),
            'Alamat sesuai KTP' => filled($employee->identity_address),
            'Alamat domisili' => filled($employee->address),
            'Provinsi domisili' => filled($employee->domicile_province),
            'Kabupaten/kota domisili' => filled($employee->domicile_city),
            'Kecamatan domisili' => filled($employee->domicile_district),
            'Kelurahan/desa domisili' => filled($employee->domicile_village),
            'Kode pos domisili' => filled($employee->domicile_postal_code),
        ]);
    }

    private function family(Employee $employee): array
    {
        $items = [
            'Nama kontak darurat' => filled($employee->emergency_contact_name),
            'Hubungan kontak darurat' => filled($employee->emergency_contact_relationship),
            'Nomor kontak darurat' => filled($employee->emergency_contact_phone),
            'Status perkawinan belum ditentukan' => filled($employee->marital_status),
        ];

        if ($employee->marital_status === 'married') {
            $items['Tambahkan data pasangan'] = $employee->familyMembers->contains('relationship', 'spouse');
        }

        return $this->section('Keluarga', $items);
    }

    private function education(Employee $employee): array
    {
        return $this->section('Pendidikan', [
            'Tambahkan minimal satu riwayat pendidikan' => $employee->educations->isNotEmpty(),
            'Tentukan pendidikan tertinggi' => $employee->educations->contains('is_highest', true),
        ]);
    }

    private function administration(Employee $employee): array
    {
        $detail = $employee->administrativeDetail;
        $items = [
            'Nama bank' => filled($detail?->bank_name),
            'Nomor rekening' => filled($detail?->bank_account_number),
            'Nama pemilik rekening' => filled($detail?->bank_account_holder),
            'Status pajak' => filled($detail?->tax_status),
            'Status BPJS Kesehatan' => filled($detail?->bpjs_health_status),
            'Status BPJS Ketenagakerjaan' => filled($detail?->bpjs_employment_status),
        ];

        if ($detail?->tax_status === 'registered') {
            $items['Nomor identitas pajak atau penanda NIK sebagai identitas pajak'] = filled($detail->tax_identification_number)
                || $detail->nik_used_as_tax_id === true;
        }
        if ($detail?->bpjs_health_status === 'active') {
            $items['Nomor BPJS Kesehatan'] = filled($detail->bpjs_health_number);
        }
        if ($detail?->bpjs_employment_status === 'active') {
            $items['Nomor BPJS Ketenagakerjaan'] = filled($detail->bpjs_employment_number);
        }

        return $this->section('Bank & BPJS', $items);
    }

    /**
     * @param  array<string, bool>  $items
     * @return array{label: string, completed: bool, percentage: int, completed_items: int, total_items: int, missing: array<int, string>}
     */
    private function section(string $label, array $items): array
    {
        $completedItems = count(array_filter($items));
        $totalItems = count($items);

        return [
            'label' => $label,
            'completed' => $completedItems === $totalItems,
            'percentage' => $totalItems === 0 ? 0 : (int) round(($completedItems / $totalItems) * 100),
            'completed_items' => $completedItems,
            'total_items' => $totalItems,
            'missing' => array_values(array_keys(array_filter($items, fn (bool $completed): bool => ! $completed))),
        ];
    }
}
