<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventParticipant;
use Database\Seeders\Support\SyntheticSeed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UatSeeder extends Seeder
{
    public function run(): void
    {
        SyntheticSeed::guard();

        DB::transaction(function (): void {
            $this->call([DatabaseSeeder::class, UserSeeder::class, EmployeeSeeder::class]);

            $name = 'UAT Kegiatan Internal 001';
            $marker = '[UAT FIXTURE] Compact staging scanner baseline.';
            $event = Event::where('name', $name)->first();
            if ($event && $event->description !== $marker) {
                throw new RuntimeException('UAT event collision; review existing data before seeding.');
            }

            $event ??= Event::create([
                'name' => $name,
                'description' => $marker,
                'event_date' => today()->toDateString(),
                'start_time' => '08:00',
                'end_time' => '17:00',
                'location' => 'Ruang UAT',
                'target_type' => 'selected',
                'status' => 'active',
                'created_by' => SyntheticSeed::user('admin@yapista.test', 'super_admin')?->id,
            ]);

            // Keep one verified fixture outside the event for rejection testing.
            foreach (SyntheticSeed::employees()->eligibleForEvents()
                ->where('employee_number', '!=', '7770923833')->get() as $employee) {
                EventParticipant::firstOrCreate([
                    'event_id' => $event->id,
                    'employee_id' => $employee->id,
                ], ['participant_status' => 'invited']);
            }
        });
    }
}
