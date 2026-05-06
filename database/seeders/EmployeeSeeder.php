<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            [
                'full_name' => 'Ahmad Fauzi',
                'institution' => 'SMK Ibnu Sina',
                'position' => 'Guru',
                'email' => 'ahmad.demo@yapista.test',
                'employee_type' => 'guru',
            ],
            [
                'full_name' => 'Siti Aminah',
                'institution' => 'SD Ibnu Sina',
                'position' => 'Guru',
                'email' => 'siti.demo@yapista.test',
                'employee_type' => 'guru',
            ],
            [
                'full_name' => 'Budi Santoso',
                'institution' => 'Universitas Ibnu Sina',
                'position' => 'Dosen',
                'email' => 'budi.demo@yapista.test',
                'employee_type' => 'dosen',
            ],
            [
                'full_name' => 'Nurul Huda',
                'institution' => 'Kantor Yayasan',
                'position' => 'Staff Yayasan',
                'email' => 'nurul.demo@yapista.test',
                'employee_type' => 'staff_yayasan',
            ],
        ];

        foreach ($employees as $employee) {
            $institution = Institution::where('name', $employee['institution'])->first();

            if (! $institution) {
                continue;
            }

            $position = Position::where('institution_id', $institution->id)
                ->where('name', $employee['position'])
                ->first();

            if (! $position) {
                continue;
            }

            Employee::updateOrCreate(
                ['email' => $employee['email']],
                [
                    'institution_id' => $institution->id,
                    'position_id' => $position->id,
                    'full_name' => $employee['full_name'],
                    'employee_type' => $employee['employee_type'],
                    'employment_status' => 'aktif',
                    'verification_status' => 'draft',
                ],
            );
        }
    }
}
