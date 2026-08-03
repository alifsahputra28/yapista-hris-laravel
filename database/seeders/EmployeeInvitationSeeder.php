<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeInvitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! class_exists(EmployeeInvitation::class)) {
            return;
        }

        $admin = User::where('email', 'admin@yapista.test')->first();
        $invitations = [
            [
                'employee_email' => 'siti.aminah@yapista.test',
                'invitation_code' => 'YAPISTA-REG-SITI01',
                'status' => 'unused',
                'expired_at' => now()->addDays(14),
                'used_at' => null,
            ],
            [
                'employee_email' => 'nurul.huda@yapista.test',
                'invitation_code' => 'YAPISTA-REG-NURUL01',
                'status' => 'unused',
                'expired_at' => now()->addDays(14),
                'used_at' => null,
            ],
            [
                'employee_email' => 'rina.marlina@yapista.test',
                'invitation_code' => 'YAPISTA-REG-RINA01',
                'status' => 'revoked',
                'expired_at' => now()->addDays(7),
                'used_at' => null,
            ],
            [
                'employee_email' => 'maya.sari@yapista.test',
                'invitation_code' => 'YAPISTA-REG-MAYA01',
                'status' => 'expired',
                'expired_at' => now()->subDay(),
                'used_at' => null,
            ],
            [
                'employee_email' => 'ahmad.fauzi@yapista.test',
                'invitation_code' => 'YAPISTA-REG-AHMAD01',
                'status' => 'used',
                'expired_at' => now()->addDays(7),
                'used_at' => now()->subDays(2),
            ],
        ];

        foreach ($invitations as $invitation) {
            $employee = Employee::where('email', $invitation['employee_email'])->first();

            if (! $employee) {
                continue;
            }

            EmployeeInvitation::updateOrCreate(
                ['invitation_code' => $invitation['invitation_code']],
                [
                    'employee_id' => $employee->id,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'status' => $invitation['status'],
                    'expired_at' => $invitation['expired_at'],
                    'used_at' => $invitation['used_at'],
                    'created_by' => $admin?->id,
                ],
            );
        }
    }
}
