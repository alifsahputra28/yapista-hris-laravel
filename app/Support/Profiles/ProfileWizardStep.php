<?php

namespace App\Support\Profiles;

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

    /** @return array<string, array{label: string, short_label: string, icon: string}> */
    public static function all(): array
    {
        return self::STEPS;
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
}
