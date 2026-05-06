<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedForInstitution('Kantor Yayasan', [
            ['name' => 'Ketua Yayasan', 'type' => 'struktural'],
            ['name' => 'Sekretaris', 'type' => 'administratif'],
            ['name' => 'Bendahara', 'type' => 'administratif'],
            ['name' => 'HR Admin', 'type' => 'administratif'],
            ['name' => 'Staff Yayasan', 'type' => 'administratif'],
            ['name' => 'Staff IT', 'type' => 'teknis'],
            ['name' => 'Staff Sarpras', 'type' => 'teknis'],
        ]);

        foreach (['TK Ibnu Sina', 'SD Ibnu Sina', 'SMP Ibnu Sina', 'SMK Ibnu Sina'] as $schoolName) {
            $this->seedForInstitution($schoolName, [
                ['name' => 'Kepala Sekolah', 'type' => 'struktural'],
                ['name' => 'Wakil Kepala Sekolah', 'type' => 'struktural'],
                ['name' => 'Guru', 'type' => 'fungsional'],
                ['name' => 'Staff TU', 'type' => 'administratif'],
                ['name' => 'Operator Sekolah', 'type' => 'teknis'],
            ]);
        }

        foreach (['STAI Ibnu Sina', 'Universitas Ibnu Sina'] as $collegeName) {
            $this->seedForInstitution($collegeName, [
                ['name' => 'Rektor', 'type' => 'struktural'],
                ['name' => 'Dekan', 'type' => 'struktural'],
                ['name' => 'Kaprodi', 'type' => 'struktural'],
                ['name' => 'Dosen', 'type' => 'fungsional'],
                ['name' => 'Staff Akademik', 'type' => 'administratif'],
            ]);
        }
    }

    /**
     * @param  array<int, array{name: string, type: string}>  $positions
     */
    private function seedForInstitution(string $institutionName, array $positions): void
    {
        $institution = Institution::where('name', $institutionName)->first();

        if (! $institution) {
            return;
        }

        foreach ($positions as $position) {
            Position::updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'name' => $position['name'],
                ],
                [
                    'type' => $position['type'],
                    'status' => 'active',
                ],
            );
        }
    }
}
