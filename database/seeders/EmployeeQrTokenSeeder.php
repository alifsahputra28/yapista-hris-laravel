<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use Database\Seeders\Support\SyntheticSeed;
use Illuminate\Database\Seeder;

class EmployeeQrTokenSeeder extends Seeder
{
    public function run(): void
    {
        SyntheticSeed::guard();
        $admin = User::where('email', 'admin@yapista.test')->first();
        $tokenService = app(EmployeeQrTokenService::class);

        SyntheticSeed::employees()->eligibleForEvents()
            ->get(['id', 'employee_number', 'verification_status', 'employment_status'])
            ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber())
            ->each(function (Employee $employee) use ($admin, $tokenService): void {
                $tokenService->generate($employee, $admin);
            });
    }
}
