<?php

namespace App\Support\Attendances;

use App\Models\Employee;
use App\Models\EmployeeQrToken;

final readonly class ScannerPayloadResolution
{
    private function __construct(
        public ?Employee $employee,
        public ?EmployeeQrToken $qrToken,
        public ?string $errorMessage,
    ) {}

    public static function secure(EmployeeQrToken $qrToken): self
    {
        return new self($qrToken->employee, $qrToken, null);
    }

    public static function legacy(Employee $employee): self
    {
        return new self($employee, null, null);
    }

    public static function rejected(string $message): self
    {
        return new self(null, null, $message);
    }

    public function isResolved(): bool
    {
        return $this->employee !== null;
    }
}
