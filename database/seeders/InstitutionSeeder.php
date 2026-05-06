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
            ['name' => 'TK Ibnu Sina', 'level' => 'TK'],
            ['name' => 'SD Ibnu Sina', 'level' => 'SD'],
            ['name' => 'SMP Ibnu Sina', 'level' => 'SMP'],
            ['name' => 'SMK Ibnu Sina', 'level' => 'SMK'],
            ['name' => 'STAI Ibnu Sina', 'level' => 'Perguruan Tinggi'],
            ['name' => 'Universitas Ibnu Sina', 'level' => 'Perguruan Tinggi'],
            ['name' => 'Kantor Yayasan', 'level' => 'Yayasan'],
        ];

        foreach ($institutions as $institution) {
            Institution::updateOrCreate(
                ['name' => $institution['name']],
                [
                    'level' => $institution['level'],
                    'status' => 'active',
                ],
            );
        }
    }
}
