<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeQrTokenSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@yapista.test')->value('id');

        Employee::query()
            ->where('verification_status', 'verified')
            ->get(['id', 'employee_number'])
            ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber())
            ->each(function (Employee $employee) use ($adminId): void {
                DB::transaction(function () use ($employee, $adminId): void {
                    $activeTokens = EmployeeQrToken::query()
                        ->where('employee_id', $employee->id)
                        ->where('is_active', true)
                        ->whereNull('revoked_at')
                        ->latest('id')
                        ->get();

                    if ($activeTokens->isNotEmpty()) {
                        $activeTokens->skip(1)->each->update([
                            'is_active' => false,
                            'revoked_at' => now(),
                        ]);

                        return;
                    }

                    EmployeeQrToken::create([
                        'employee_id' => $employee->id,
                        'token' => $this->uniqueToken(),
                        'is_active' => true,
                        'issued_at' => now(),
                        'revoked_at' => null,
                        'created_by' => $adminId,
                    ]);
                });
            });
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (EmployeeQrToken::where('token', $token)->exists());

        return $token;
    }
}
