<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = [
            ['name' => 'TK Ibnu Sina', 'level' => 'U2'],
            ['name' => 'SD Ibnu Sina', 'level' => 'U2'],
            ['name' => 'SMP Ibnu Sina', 'level' => 'U2'],
            ['name' => 'SMK Ibnu Sina', 'level' => 'U2'],
            ['name' => 'STAI Ibnu Sina', 'level' => 'U2'],
            ['name' => 'Universitas Ibnu Sina', 'level' => 'U2'],
            ['name' => 'Kantor Yayasan', 'level' => 'U1'],
        ];

        foreach ($institutions as $institution) {
            Institution::firstOrCreate(
                ['name' => $institution['name']],
                [
                    'level' => $institution['level'],
                    'status' => 'active',
                ],
            );
        }
    }
}
