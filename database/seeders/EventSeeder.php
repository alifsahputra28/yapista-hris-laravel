<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@yapista.test')->first();
        $hr = User::where('email', 'hr@yapista.test')->first();

        $events = [
            [
                'name' => 'Rapat Koordinasi Yayasan',
                'event_date' => today()->toDateString(),
                'start_time' => '08:00',
                'end_time' => '10:00',
                'location' => 'Aula YAPISTA',
                'description' => 'Koordinasi rutin lintas unit kerja YAPISTA.',
                'target_type' => 'all',
                'status' => 'active',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Workshop Guru dan Dosen',
                'event_date' => today()->addDay()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '12:00',
                'location' => 'Ruang Seminar',
                'description' => 'Workshop peningkatan kapasitas pendidik.',
                'target_type' => 'position',
                'status' => 'draft',
                'created_by' => $hr?->id,
            ],
            [
                'name' => 'Halal Bihalal YAPISTA',
                'event_date' => today()->subDays(7)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '11:00',
                'location' => 'Lapangan YAPISTA',
                'description' => 'Silaturahmi keluarga besar YAPISTA.',
                'target_type' => 'all',
                'status' => 'closed',
                'created_by' => $admin?->id,
            ],
            [
                'name' => 'Rapat Internal SMK',
                'event_date' => today()->addDays(3)->toDateString(),
                'start_time' => '13:00',
                'end_time' => '15:00',
                'location' => 'Ruang Rapat SMK',
                'description' => 'Rapat internal pegawai SMK Ibnu Sina.',
                'target_type' => 'institution',
                'status' => 'active',
                'created_by' => $hr?->id,
            ],
            [
                'name' => 'Sosialisasi Program Dibatalkan',
                'event_date' => today()->addDays(5)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:30',
                'location' => 'Aula YAPISTA',
                'description' => 'Contoh kegiatan dengan status dibatalkan.',
                'target_type' => 'selected',
                'status' => 'cancelled',
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($events as $event) {
            Event::updateOrCreate(
                ['name' => $event['name']],
                [
                    'event_date' => $event['event_date'],
                    'start_time' => $event['start_time'],
                    'end_time' => $event['end_time'],
                    'location' => $event['location'],
                    'description' => $event['description'],
                    'target_type' => $event['target_type'],
                    'status' => $event['status'],
                    'created_by' => $event['created_by'],
                ],
            );
        }
    }
}
