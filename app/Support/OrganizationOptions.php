<?php

namespace App\Support;

final class OrganizationOptions
{
    /** @var array<string, array{name:string,description:string,examples:list<string>}> */
    public const UNIT_LEVELS = [
        'U1' => [
            'name' => 'Induk / Yayasan',
            'description' => 'Entitas organisasi tertinggi.',
            'examples' => ['YAPISTA'],
        ],
        'U2' => [
            'name' => 'Institusi / Satuan Pendidikan',
            'description' => 'Lembaga utama yang berada langsung di bawah yayasan.',
            'examples' => ['Universitas Ibnu Sina', 'STAI Ibnu Sina', 'SMK Ibnu Sina Batam', 'SMP Ibnu Sina', 'SD Ibnu Sina', 'TK Ibnu Sina', 'Klinik'],
        ],
        'U3' => [
            'name' => 'Fakultas / Bidang / Bagian Utama',
            'description' => 'Unit organisasi besar di bawah institusi atau yayasan.',
            'examples' => ['Fakultas Ilmu Kesehatan', 'Fakultas Ekonomi dan Bisnis', 'Fakultas Sains dan Teknologi', 'Rektorat', 'Bidang Keuangan', 'Bidang Umum'],
        ],
        'U4' => [
            'name' => 'Program Studi / Unit / Bagian',
            'description' => 'Unit kerja operasional atau akademik di bawah U3 atau langsung di bawah institusi.',
            'examples' => ['Program Studi K3', 'Program Studi Kesehatan Lingkungan', 'BAAK', 'BAUK', 'LPTI', 'Tata Usaha'],
        ],
        'U5' => [
            'name' => 'Subunit / Tim',
            'description' => 'Unit kerja paling kecil untuk kebutuhan operasional.',
            'examples' => ['Laboratorium', 'Tim Humas', 'Subbagian', 'Tim IT'],
        ],
    ];

    /** @var array<string, array{name:string,description:string,examples:list<string>}> */
    public const POSITION_TYPES = [
        'ORG' => [
            'name' => 'Organ Yayasan',
            'description' => 'Jabatan yang merupakan organ tata kelola yayasan.',
            'examples' => ['Pembina', 'Pengawas'],
        ],
        'STR' => [
            'name' => 'Struktural / Manajerial',
            'description' => 'Jabatan yang memiliki fungsi kepemimpinan, pengelolaan, atau koordinasi organisasi.',
            'examples' => ['Ketua Yayasan', 'Rektor', 'Wakil Rektor', 'Dekan', 'Kepala Sekolah', 'Kepala Program Studi', 'Kepala BAAK', 'Kepala BAUK', 'Kepala Laboratorium'],
        ],
        'FNG' => [
            'name' => 'Fungsional / Profesional',
            'description' => 'Jabatan berdasarkan profesi, kompetensi, atau keahlian tertentu.',
            'examples' => ['Dosen', 'Guru', 'Akuntan', 'Pustakawan', 'Tenaga Kesehatan'],
        ],
        'ADM' => [
            'name' => 'Administratif / Pelaksana',
            'description' => 'Jabatan pelaksana administrasi dan pelayanan organisasi.',
            'examples' => ['Staf Keuangan', 'Staf Akademik', 'Tata Usaha', 'Front Office', 'Admin', 'Staf IT'],
        ],
        'OPS' => [
            'name' => 'Operasional / Pendukung',
            'description' => 'Jabatan operasional dan penunjang kegiatan organisasi.',
            'examples' => ['Security', 'Cleaning Service', 'Driver', 'Kebersihan', 'Transportasi', 'Penjaga'],
        ],
    ];

    /** @return list<string> */
    public static function unitLevelCodes(): array
    {
        return array_keys(self::UNIT_LEVELS);
    }

    /** @return list<string> */
    public static function positionTypeCodes(): array
    {
        return array_keys(self::POSITION_TYPES);
    }

    /** @return list<string> */
    public static function unitLevelDatabaseValues(string $code): array
    {
        return match ($code) {
            'U1' => ['U1', 'Yayasan'],
            'U2' => ['U2', 'TK', 'SD', 'SMP', 'SMK', 'Perguruan Tinggi'],
            'U4' => ['U4', 'Unit'],
            default => [$code],
        };
    }

    /** @return list<string> */
    public static function positionTypeDatabaseValues(string $code): array
    {
        return match ($code) {
            'STR' => ['STR', 'struktural'],
            'FNG' => ['FNG', 'fungsional'],
            'ADM' => ['ADM', 'administratif', 'teknis'],
            'OPS' => ['OPS', 'operasional', 'teknis'],
            default => [$code],
        };
    }

    public static function normalizeUnitLevel(?string $value): ?string
    {
        $value = trim((string) $value);
        $canonical = strtoupper($value);

        if (isset(self::UNIT_LEVELS[$canonical])) {
            return $canonical;
        }

        return match (mb_strtolower($value)) {
            'yayasan' => 'U1',
            'tk', 'sd', 'smp', 'smk', 'perguruan tinggi' => 'U2',
            'unit' => 'U4',
            default => null,
        };
    }

    public static function normalizePositionType(?string $value, ?string $positionName = null): ?string
    {
        $value = trim((string) $value);
        $canonical = strtoupper($value);

        if (isset(self::POSITION_TYPES[$canonical])) {
            return $canonical;
        }

        return match (mb_strtolower($value)) {
            'struktural' => 'STR',
            'fungsional' => 'FNG',
            'administratif' => 'ADM',
            'operasional' => 'OPS',
            'teknis' => self::isLegacyAdministrativeTechnicalPosition($positionName) ? 'ADM' : 'OPS',
            default => null,
        };
    }

    public static function unitLevelLabel(?string $value): string
    {
        $code = self::normalizeUnitLevel($value);

        return $code ? $code.' — '.self::UNIT_LEVELS[$code]['name'] : ((string) $value ?: '-');
    }

    public static function positionTypeLabel(?string $value, ?string $positionName = null): string
    {
        $code = self::normalizePositionType($value, $positionName);

        return $code ? $code.' — '.self::POSITION_TYPES[$code]['name'] : ((string) $value ?: '-');
    }

    private static function isLegacyAdministrativeTechnicalPosition(?string $positionName): bool
    {
        return in_array(mb_strtolower(trim((string) $positionName)), ['staff it', 'staf it'], true);
    }
}
