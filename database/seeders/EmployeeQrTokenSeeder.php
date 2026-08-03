<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class EmployeeQrTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! class_exists(EmployeeQrToken::class) || ! Schema::hasTable('employee_qr_tokens')) {
            return;
        }

        $admin = User::where('email', 'admin@yapista.test')->first();

        $employees = Employee::query()
            ->where('verification_status', 'verified')
            ->whereIn('email', [
                'ahmad.fauzi@yapista.test',
                'budi.santoso@yapista.test',
                'andi.pratama@yapista.test',
                'dewi.lestari@yapista.test',
                'fajar.ramadhan@yapista.test',
                'rahmat.hidayat@yapista.test',
                'hendra.wijaya@yapista.test',
            ])
            ->get();

        foreach ($employees as $employee) {
            $code = $employee->employee_number;

            if (! $code) {
                continue;
            }

            EmployeeQrToken::where('employee_id', $employee->id)
                ->where('token', '!=', $code)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'revoked_at' => now()->subDay(),
                ]);

            EmployeeQrToken::updateOrCreate(
                ['token' => $code],
                [
                    'employee_id' => $employee->id,
                    'is_active' => true,
                    'issued_at' => $employee->verified_at ?? $employee->created_at,
                    'revoked_at' => null,
                    'created_by' => $admin?->id,
                ],
            );
        }

        $ahmad = Employee::where('email', 'ahmad.fauzi@yapista.test')->first();

        if ($ahmad) {
            EmployeeQrToken::updateOrCreate(
                ['token' => 'YAPISTA-QR-OLD-AHMAD'],
                [
                    'employee_id' => $ahmad->id,
                    'is_active' => false,
                    'issued_at' => $ahmad->join_date?->copy()->subDay() ?? $ahmad->created_at,
                    'revoked_at' => $ahmad->join_date?->copy()->addDay() ?? $ahmad->updated_at,
                    'created_by' => $admin?->id,
                ],
            );
        }
    }
}
