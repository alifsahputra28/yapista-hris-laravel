<?php

namespace Tests\Unit;

use App\Services\EmployeeNikProtectionService;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class EmployeeNikProtectionServiceTest extends TestCase
{
    private EmployeeNikProtectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EmployeeNikProtectionService::class);
    }

    public function test_normalization_accepts_sixteen_digits_and_removes_spaces(): void
    {
        $this->assertSame('3201010101010001', $this->service->normalize('3201010101010001'));
        $this->assertSame('3201010101010001', $this->service->normalize('3201 0101 0101 0001'));
        $this->assertNull($this->service->normalize(null));
        $this->assertNull($this->service->normalize('  '));
    }

    public function test_normalization_rejects_invalid_values_without_echoing_them(): void
    {
        foreach (['320101010101000A', '123', '32010101010100011'] as $invalid) {
            try {
                $this->service->normalize($invalid);
                $this->fail('NIK invalid seharusnya ditolak.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('NIK harus terdiri dari 16 digit angka.', $exception->getMessage());
                $this->assertStringNotContainsString($invalid, $exception->getMessage());
            }
        }
    }

    public function test_encryption_round_trip_and_masking_are_safe(): void
    {
        $nik = '3201010101010001';
        $ciphertext = $this->service->encrypt($nik);

        $this->assertNotNull($ciphertext);
        $this->assertNotSame($nik, $ciphertext);
        $this->assertSame($nik, $this->service->decrypt($ciphertext));
        $this->assertSame('************0001', $this->service->mask($nik));
        $this->assertNull($this->service->encrypt(null));
        $this->assertNull($this->service->decrypt(null));
        $this->assertNull($this->service->mask(null));
    }

    public function test_hmac_is_deterministic_keyed_and_distinguishes_inputs(): void
    {
        $nik = '3201010101010001';
        $lookup = $this->service->lookup($nik);

        $this->assertSame($lookup, $this->service->lookup($nik));
        $this->assertNotSame($lookup, $this->service->lookup('3201010101010002'));
        $this->assertSame(
            hash_hmac('sha256', $nik, (string) config('security.employee_nik_lookup_key')),
            $lookup,
        );
        $this->assertNotSame(hash('sha256', $nik), $lookup);
    }

    public function test_missing_lookup_key_fails_without_exposing_nik(): void
    {
        config()->set('security.employee_nik_lookup_key');
        $nik = '3201010101010001';

        try {
            $this->service->lookup($nik);
            $this->fail('Lookup tanpa key seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('EMPLOYEE_NIK_LOOKUP_KEY', $exception->getMessage());
            $this->assertStringNotContainsString($nik, $exception->getMessage());
        }
    }
}
