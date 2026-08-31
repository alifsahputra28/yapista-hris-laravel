<?php

namespace Database\Seeders;

use Database\Seeders\Support\SyntheticSeed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        SyntheticSeed::guard(['local', 'testing']);

        DB::transaction(function (): void {
            $this->call([
                DatabaseSeeder::class,
                UserSeeder::class,
                EmployeeSeeder::class,
                EmployeeInvitationSeeder::class,
                EmployeeDocumentSeeder::class,
                EventSeeder::class,
                EventParticipantSeeder::class,
                EventAttendanceSeeder::class,
            ]);
        });
    }
}
