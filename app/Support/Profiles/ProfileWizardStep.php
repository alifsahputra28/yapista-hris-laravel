<?php

namespace App\Support\Profiles;

use App\Models\Employee;

class ProfileWizardStep
{
    public const STEPS = [
        'identification' => ['label' => 'Identitas Pribadi', 'short_label' => 'Identitas', 'icon' => 'ti-id'],
        'contact-address' => ['label' => 'Kontak & Alamat', 'short_label' => 'Kontak', 'icon' => 'ti-map-pin'],
        'family' => ['label' => 'Keluarga', 'short_label' => 'Keluarga', 'icon' => 'ti-users'],
        'education' => ['label' => 'Pendidikan', 'short_label' => 'Pendidikan', 'icon' => 'ti-school'],
        'administration' => ['label' => 'Bank & BPJS', 'short_label' => 'Administrasi', 'icon' => 'ti-building-bank'],
        'review' => ['label' => 'Dokumen & Kirim', 'short_label' => 'Dokumen', 'icon' => 'ti-clipboard-check'],
    ];

    public const EXISTING_EMPLOYEE_STEPS = [
        'identification',
        'contact-address',
        'review',
    ];

    /** @return array<string, array{label: string, short_label: string, icon: string}> */
    public static function all(): array
    {
        return self::STEPS;
    }

    /** @return array<string, array{label: string, short_label: string, icon: string}> */
    public static function for(Employee $employee): array
    {
        if (! $employee->isVerified()) {
            return self::all();
        }

        $steps = array_intersect_key(self::STEPS, array_flip(self::EXISTING_EMPLOYEE_STEPS));
        $steps['review'] = ['label' => 'Periksa & Selesai', 'short_label' => 'Periksa', 'icon' => 'ti-clipboard-check'];

        return $steps;
    }

    public static function exists(string $step): bool
    {
        return array_key_exists($step, self::STEPS);
    }

    public static function previous(string $step): ?string
    {
        $steps = array_keys(self::STEPS);
        $index = array_search($step, $steps, true);

        return is_int($index) && $index > 0 ? $steps[$index - 1] : null;
    }

    public static function next(string $step): ?string
    {
        $steps = array_keys(self::STEPS);
        $index = array_search($step, $steps, true);

        return is_int($index) && isset($steps[$index + 1]) ? $steps[$index + 1] : null;
    }

    /** @param  array<string, mixed>  $steps */
    public static function previousIn(string $step, array $steps): ?string
    {
        $keys = array_keys($steps);
        $index = array_search($step, $keys, true);

        return is_int($index) && $index > 0 ? $keys[$index - 1] : null;
    }

    /** @param  array<string, mixed>  $steps */
    public static function nextIn(string $step, array $steps): ?string
    {
        $keys = array_keys($steps);
        $index = array_search($step, $keys, true);

        return is_int($index) && isset($keys[$index + 1]) ? $keys[$index + 1] : null;
    }
}
