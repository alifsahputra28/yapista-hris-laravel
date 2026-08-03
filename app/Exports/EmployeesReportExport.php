<?php

namespace App\Exports;

use App\Models\Employee;
use App\Support\Reports\SimpleXlsxWriter;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeesReportExport
{
    /**
     * @param  Builder<Employee>  $query
     */
    public function __construct(private readonly Builder $query) {}

    public function download(string $filename): StreamedResponse
    {
        $employees = (clone $this->query)
            ->with(['institution', 'position', 'user'])
            ->get();

        $rows = $employees->values()->map(function (Employee $employee, int $index): array {
            return [
                $index + 1,
                $employee->full_name,
                $employee->employee_number ?: 'Belum diisi',
                $employee->institution?->name ?: '-',
                $employee->position?->name ?: '-',
                $this->employeeTypeLabel($employee->employee_type),
                $employee->email ?: '-',
                $employee->phone ?: '-',
                $this->employmentStatusLabel($employee->employment_status),
                $this->verificationStatusLabel($employee->verification_status),
                $employee->user_id ? 'Sudah Registrasi' : 'Belum Registrasi',
            ];
        });

        return SimpleXlsxWriter::download($filename, [
            'No',
            'Nama Pegawai',
            'NUP / Nomor Pegawai',
            'Unit Kerja',
            'Jabatan',
            'Jenis Pegawai',
            'Email',
            'Nomor HP',
            'Status Kerja',
            'Status Verifikasi',
            'Status Registrasi',
        ], $rows, 'Laporan Pegawai');
    }

    private function employeeTypeLabel(?string $value): string
    {
        return [
            'guru' => 'Guru',
            'dosen' => 'Dosen',
            'tenaga_kependidikan' => 'Tenaga Kependidikan',
            'staff_yayasan' => 'Staff Yayasan',
            'security' => 'Security',
            'cleaning_service' => 'Cleaning Service',
            'driver' => 'Driver',
            'teknisi' => 'Teknisi',
        ][$value] ?? ($value ?: '-');
    }

    private function employmentStatusLabel(?string $value): string
    {
        return [
            'aktif' => 'Aktif',
            'kontrak' => 'Kontrak',
            'honorer' => 'Honorer',
            'part_time' => 'Part Time',
            'nonaktif' => 'Nonaktif',
            'resign' => 'Resign',
        ][$value] ?? ($value ?: '-');
    }

    private function verificationStatusLabel(?string $value): string
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
        ][$value] ?? ($value ?: '-');
    }
}
