<?php

namespace App\Support\Imports;

final class EmployeeImportColumns
{
    /** @var array<string, array{label: string, required: bool}> */
    public const DEFINITIONS = [
        'employee_number' => ['label' => 'NUP', 'required' => false],
        'full_name' => ['label' => 'Nama Lengkap', 'required' => true],
        'login_email' => ['label' => 'Email Login', 'required' => true],
        'personal_email' => ['label' => 'Email Pribadi', 'required' => false],
        'institution_name' => ['label' => 'Unit Kerja', 'required' => true],
        'position_name' => ['label' => 'Jabatan', 'required' => true],
        'employee_type' => ['label' => 'Jenis Pegawai', 'required' => true],
        'employment_status' => ['label' => 'Status Kerja', 'required' => true],
        'join_date' => ['label' => 'Tanggal Masuk', 'required' => false],
    ];

    /** @var array<string, string> */
    public const EMPLOYEE_TYPES = [
        'guru' => 'Guru',
        'dosen' => 'Dosen',
        'tenaga_kependidikan' => 'Tenaga Kependidikan',
        'staff_yayasan' => 'Staff Yayasan',
        'security' => 'Security',
        'cleaning_service' => 'Cleaning Service',
        'driver' => 'Driver',
        'teknisi' => 'Teknisi',
    ];

    /** @var array<string, string> */
    public const EMPLOYMENT_STATUSES = [
        'aktif' => 'Aktif',
        'kontrak' => 'Kontrak',
        'honorer' => 'Honorer',
        'part_time' => 'Part Time',
        'nonaktif' => 'Nonaktif',
        'resign' => 'Resign',
    ];

    /** @return list<string> */
    public static function headers(): array
    {
        return array_column(self::DEFINITIONS, 'label');
    }

    /** @return list<string> */
    public static function requiredLabels(): array
    {
        return self::labelsByRequirement(true);
    }

    /** @return list<string> */
    public static function optionalLabels(): array
    {
        return self::labelsByRequirement(false);
    }

    /** @return array<string, string> */
    public static function normalizedHeaderMap(): array
    {
        $map = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            $map[self::normalizeLabel($definition['label'])] = $key;
        }

        return $map;
    }

    public static function normalizeChoice(?string $value, array $choices): ?string
    {
        $normalized = self::normalizeLabel((string) $value);

        foreach ($choices as $key => $label) {
            if ($normalized === self::normalizeLabel($key) || $normalized === self::normalizeLabel($label)) {
                return $key;
            }
        }

        return null;
    }

    private static function normalizeLabel(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($value)) ?? '');
    }

    /** @return list<string> */
    private static function labelsByRequirement(bool $required): array
    {
        return array_values(array_map(
            fn (array $definition): string => $definition['label'],
            array_filter(self::DEFINITIONS, fn (array $definition): bool => $definition['required'] === $required)
        ));
    }
}
