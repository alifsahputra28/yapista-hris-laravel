<?php

namespace App\Rules;

use App\Models\Employee;
use App\Services\EmployeeNikProtectionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class UniqueEmployeeNik implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreEmployeeId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $service = app(EmployeeNikProtectionService::class);

        try {
            $normalized = $service->normalize((string) $value);
            $lookup = $service->lookup($normalized);
        } catch (InvalidArgumentException) {
            return;
        }

        $duplicate = Employee::query()
            ->where(function ($query) use ($lookup, $normalized): void {
                $query->where('nik_lookup', $lookup)
                    ->orWhere(function ($query) use ($normalized): void {
                        $query->whereNull('nik_lookup')->where('nik', $normalized);
                    });
            })
            ->when($this->ignoreEmployeeId !== null, fn ($query) => $query->whereKeyNot($this->ignoreEmployeeId))
            ->exists();

        if ($duplicate) {
            $fail('NIK sudah digunakan oleh pegawai lain.');
        }
    }
}
