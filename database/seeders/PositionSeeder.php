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
            ['name' => 'Ketua Yayasan', 'type' => 'STR'],
            ['name' => 'Sekretaris', 'type' => 'ADM'],
            ['name' => 'Bendahara', 'type' => 'ADM'],
            ['name' => 'HR Admin', 'type' => 'ADM'],
            ['name' => 'Staff Yayasan', 'type' => 'ADM'],
            ['name' => 'Staff IT', 'type' => 'ADM'],
            ['name' => 'Staff Sarpras', 'type' => 'OPS'],
        ]);

        foreach (['TK Ibnu Sina', 'SD Ibnu Sina', 'SMP Ibnu Sina', 'SMK Ibnu Sina'] as $schoolName) {
            $this->seedForInstitution($schoolName, [
                ['name' => 'Kepala Sekolah', 'type' => 'STR'],
                ['name' => 'Wakil Kepala Sekolah', 'type' => 'STR'],
                ['name' => 'Guru', 'type' => 'FNG'],
                ['name' => 'Staff TU', 'type' => 'ADM'],
                ['name' => 'Operator Sekolah', 'type' => 'ADM'],
            ]);
        }

        foreach (['STAI Ibnu Sina', 'Universitas Ibnu Sina'] as $collegeName) {
            $this->seedForInstitution($collegeName, [
                ['name' => 'Rektor', 'type' => 'STR'],
                ['name' => 'Dekan', 'type' => 'STR'],
                ['name' => 'Kaprodi', 'type' => 'STR'],
                ['name' => 'Dosen', 'type' => 'FNG'],
                ['name' => 'Staff Akademik', 'type' => 'ADM'],
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
            Position::firstOrCreate(
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
