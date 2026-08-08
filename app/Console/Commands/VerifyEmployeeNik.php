<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\EmployeeNikProtectionService;
use Illuminate\Console\Command;
use RuntimeException;

class VerifyEmployeeNik extends Command
{
    protected $signature = 'employee-security:verify-nik';

    protected $description = 'Verify encrypted employee NIK data without exposing sensitive values';

    public function handle(EmployeeNikProtectionService $service): int
    {
        $issues = 0;

        Employee::query()->orderBy('id')->chunkById(200, function ($employees) use ($service, &$issues): void {
            foreach ($employees as $employee) {
                $legacy = $employee->getRawOriginal('nik');
                $encrypted = $employee->getRawOriginal('nik_encrypted');
                $lookup = $employee->getRawOriginal('nik_lookup');

                if (filled($legacy)) {
                    $this->issue($employee, 'legacy_plaintext_present', $issues);
                }

                if (filled($encrypted) && blank($lookup)) {
                    $this->issue($employee, 'lookup_missing', $issues);
                    continue;
                }

                if (filled($lookup) && blank($encrypted)) {
                    $this->issue($employee, 'ciphertext_missing', $issues);
                    continue;
                }

                if (blank($encrypted) && blank($lookup)) {
                    continue;
                }

                try {
                    $decrypted = $service->decrypt($encrypted);
                    $expectedLookup = $service->lookup($decrypted);
                } catch (RuntimeException) {
                    $this->issue($employee, 'decrypt_failed', $issues);
                    continue;
                }

                if ($decrypted === null || preg_match('/^\d{16}$/', $decrypted) !== 1) {
                    $this->issue($employee, 'invalid_decrypted_format', $issues);
                    continue;
                }

                if (! hash_equals((string) $lookup, (string) $expectedLookup)) {
                    $this->issue($employee, 'lookup_mismatch', $issues);
                }
            }
        });

        $duplicateLookups = Employee::query()
            ->select('nik_lookup')
            ->whereNotNull('nik_lookup')
            ->groupBy('nik_lookup')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $duplicateLookups->each(function (): void {
                $this->line('Employee NUP: unavailable | issue: duplicate_lookup');
            });
        $issues += $duplicateLookups->count();

        $this->info("Verification complete. Issues: {$issues}");

        return $issues === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function issue(Employee $employee, string $type, int &$issues): void
    {
        $identifier = $employee->employee_number ?: "ID {$employee->id}";
        $this->line("Employee {$identifier} | issue: {$type}");
        $issues++;
    }
}
