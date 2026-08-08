<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use RuntimeException;

class EmployeeNikProtectionService
{
    public function normalize(?string $nik): ?string
    {
        if ($nik === null || trim($nik) === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim($nik));

        if (! is_string($normalized) || preg_match('/^\d{16}$/', $normalized) !== 1) {
            throw new InvalidArgumentException('NIK harus terdiri dari 16 digit angka.');
        }

        return $normalized;
    }

    public function encrypt(?string $nik): ?string
    {
        $normalized = $this->normalize($nik);

        return $normalized === null ? null : Crypt::encryptString($normalized);
    }

    public function decrypt(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return null;
        }

        try {
            return $this->normalize(Crypt::decryptString($ciphertext));
        } catch (DecryptException|InvalidArgumentException $exception) {
            throw new RuntimeException('Data NIK tidak dapat dibuka.', previous: $exception);
        }
    }

    public function lookup(?string $nik): ?string
    {
        $normalized = $this->normalize($nik);

        return $normalized === null
            ? null
            : hash_hmac('sha256', $normalized, $this->lookupKey());
    }

    public function mask(?string $nik, int $visible = 4): ?string
    {
        $normalized = $this->normalize($nik);

        if ($normalized === null) {
            return null;
        }

        $visible = max(0, min($visible, strlen($normalized)));

        return str_repeat('*', strlen($normalized) - $visible)
            .($visible > 0 ? substr($normalized, -$visible) : '');
    }

    private function lookupKey(): string
    {
        $key = config('security.employee_nik_lookup_key');

        if (! is_string($key) || strlen($key) < 32) {
            throw new RuntimeException('EMPLOYEE_NIK_LOOKUP_KEY belum dikonfigurasi dengan aman.');
        }

        return $key;
    }
}
