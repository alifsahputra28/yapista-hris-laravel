<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use Illuminate\Database\Seeder;

class EmployeeQrTokenSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@yapista.test')->first();
        $tokenService = app(EmployeeQrTokenService::class);

        Employee::query()
            ->where('verification_status', 'verified')
            ->get(['id', 'employee_number', 'verification_status', 'employment_status'])
            ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber())
            ->each(function (Employee $employee) use ($admin, $tokenService): void {
                $tokenService->generate($employee, $admin);
            });
    }
}
