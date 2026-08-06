<?php

namespace App\Support\Documents;

class EmployeeDocumentType
{
    public const ALL = [
        'ktp' => 'Kartu Tanda Penduduk',
        'kk' => 'Kartu Keluarga',
        'buku_rekening' => 'Buku/Bukti Rekening',
        'bpjs_kesehatan' => 'Kartu BPJS Kesehatan',
        'bpjs_ketenagakerjaan' => 'Kartu BPJS Ketenagakerjaan',
        'dokumen_pajak' => 'Dokumen Pajak',
        'ijazah' => 'Ijazah',
        'transkrip' => 'Transkrip Nilai',
        'sertifikat' => 'Sertifikat',
        'sk_kontrak' => 'SK/Kontrak Kerja',
        'sk_pengangkatan' => 'SK Pengangkatan',
    ];

    public const GENERAL_EMPLOYEE_TYPES = [
        'ktp',
        'kk',
        'buku_rekening',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
        'dokumen_pajak',
    ];

    public const EDUCATION_TYPES = ['ijazah', 'transkrip'];

    public const CERTIFICATION_TYPES = ['sertifikat'];

    public const HR_TYPES = ['sk_kontrak', 'sk_pengangkatan'];

    /** @return array<string, string> */
    public static function employeeUploadable(): array
    {
        return array_intersect_key(
            self::ALL,
            array_flip([...self::GENERAL_EMPLOYEE_TYPES, ...self::EDUCATION_TYPES, ...self::CERTIFICATION_TYPES])
        );
    }

    /** @return array<string, string> */
    public static function generalEmployeeTypes(): array
    {
        return array_intersect_key(self::ALL, array_flip(self::GENERAL_EMPLOYEE_TYPES));
    }

    public static function label(string $type): string
    {
        return self::ALL[$type] ?? $type;
    }

    public static function isGeneral(string $type): bool
    {
        return in_array($type, self::GENERAL_EMPLOYEE_TYPES, true);
    }

    public static function isEducation(string $type): bool
    {
        return in_array($type, self::EDUCATION_TYPES, true);
    }

    public static function isCertification(string $type): bool
    {
        return in_array($type, self::CERTIFICATION_TYPES, true);
    }

    public static function employeeMayUpload(string $type): bool
    {
        return array_key_exists($type, self::employeeUploadable());
    }
}
