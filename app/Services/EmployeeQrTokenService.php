<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeQrTokenService
{
    public const PAYLOAD_PREFIX = 'YAPISTA:EMPLOYEE:';

    public const TOKEN_LENGTH = 64;

    public function generate(Employee $employee, ?User $creator = null): EmployeeQrToken
    {
        $this->ensureEligible($employee);

        return DB::transaction(function () use ($employee, $creator): EmployeeQrToken {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $activeTokens = EmployeeQrToken::query()
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->latest('id')
                ->get();

            if ($activeTokens->isNotEmpty()) {
                $activeTokens->skip(1)->each->revoke();

                return $activeTokens->first();
            }

            return $this->createToken($employee, $creator);
        });
    }

    public function regenerate(Employee $employee, ?User $creator = null): EmployeeQrToken
    {
        $this->ensureEligible($employee);

        return DB::transaction(function () use ($employee, $creator): EmployeeQrToken {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            EmployeeQrToken::query()
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get()
                ->each->revoke();

            return $this->createToken($employee, $creator);
        });
    }

    public function revoke(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            EmployeeQrToken::query()
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get()
                ->each->revoke();
        });
    }

    public function resolvePayload(string $payload): ?EmployeeQrToken
    {
        $rawToken = $this->parsePayload($payload);

        if ($rawToken === null) {
            return null;
        }

        return EmployeeQrToken::query()
            ->with('employee')
            ->where('token_hash', $this->hash($rawToken))
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->first();
    }

    public function parsePayload(string $payload): ?string
    {
        $payload = trim($payload);
        $pattern = '/\A'.preg_quote(self::PAYLOAD_PREFIX, '/').'([A-Za-z0-9]{'.self::TOKEN_LENGTH.'})\z/';

        return preg_match($pattern, $payload, $matches) === 1
            ? $matches[1]
            : null;
    }

    public function payloadFor(EmployeeQrToken $token): string
    {
        $rawToken = $token->token_encrypted;

        if (! is_string($rawToken) || preg_match('/\A[A-Za-z0-9]{'.self::TOKEN_LENGTH.'}\z/', $rawToken) !== 1) {
            throw new DomainException('Token QR tidak dapat digunakan. Silakan buat ulang QR Code.');
        }

        return self::PAYLOAD_PREFIX.$rawToken;
    }

    public function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    private function createToken(Employee $employee, ?User $creator): EmployeeQrToken
    {
        do {
            $rawToken = Str::random(self::TOKEN_LENGTH);
            $tokenHash = $this->hash($rawToken);
        } while (EmployeeQrToken::query()->where('token_hash', $tokenHash)->exists());

        return EmployeeQrToken::create([
            'employee_id' => $employee->id,
            'token_hash' => $tokenHash,
            'token_encrypted' => $rawToken,
            'is_active' => true,
            'issued_at' => now(),
            'revoked_at' => null,
            'created_by' => $creator?->id,
        ]);
    }

    private function ensureEligible(Employee $employee): void
    {
        if (! $employee->isVerified()) {
            throw new DomainException('QR Code hanya tersedia untuk pegawai yang sudah terverifikasi.');
        }

        if (! $employee->hasValidEmployeeNumber()) {
            throw new DomainException('NUP / Nomor Pegawai harus terdiri dari 10 digit angka.');
        }

        if (in_array($employee->employment_status, ['nonaktif', 'resign'], true)) {
            throw new DomainException('Status kepegawaian tidak memenuhi syarat untuk memiliki QR Code.');
        }
    }
}
