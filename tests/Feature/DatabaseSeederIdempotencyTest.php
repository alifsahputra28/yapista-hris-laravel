<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_idempotent_and_preserves_employee_account_links(): void
    {
        Carbon::setTestNow('2026-08-03 09:00:00');

        try {
            $this->seed(DatabaseSeeder::class);
            $this->assertSame(0, DB::table('employee_documents')->count());

            $linkedUser = User::factory()->create([
                'email' => 'linked.employee@yapista.test',
                'role' => 'pegawai',
                'status' => 'active',
            ]);
            $budi = Employee::where('email', 'budi.santoso@yapista.test')->firstOrFail();
            $budi->update(['user_id' => $linkedUser->id]);
            $existingDocument = EmployeeDocument::create([
                'employee_id' => $budi->id,
                'document_type' => 'ktp',
                'file_path' => 'employee-documents/'.$budi->id.'/existing.pdf',
                'original_name' => 'existing.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'status' => 'pending',
                'uploaded_at' => now(),
            ]);

            $counts = $this->tableCounts();
            $this->assertSame([
                'users' => 5,
                'institutions' => 7,
                'positions' => 37,
                'employees' => 12,
                'employee_invitations' => 5,
                'employee_documents' => 1,
                'employee_qr_tokens' => 8,
                'events' => 5,
                'event_participants' => 21,
                'event_attendances' => 9,
            ], $counts);
            $event = Event::where('name', 'Rapat Koordinasi Yayasan')->firstOrFail();
            $eventId = $event->id;

            Carbon::setTestNow('2026-08-10 09:00:00');
            $this->seed(DatabaseSeeder::class);

            $this->assertSame($counts, $this->tableCounts());
            $this->assertSame($eventId, Event::where('name', 'Rapat Koordinasi Yayasan')->value('id'));
            $this->assertSame(
                '2026-08-10',
                Event::where('name', 'Rapat Koordinasi Yayasan')->firstOrFail()->event_date->toDateString()
            );
            $this->assertSame($linkedUser->id, $budi->fresh()->user_id);

            $demoUser = User::where('email', 'pegawai@yapista.test')->firstOrFail();
            $ahmad = Employee::where('email', 'ahmad.fauzi@yapista.test')->firstOrFail();
            $this->assertSame($demoUser->id, $ahmad->user_id);
            $this->assertDatabaseHas('employee_documents', ['id' => $existingDocument->id]);
            $this->assertSame(0, Employee::query()->whereNotNull('nup')->count());
            $this->assertSame(0, Employee::query()->whereNotNull('foundation_registry_number')->count());

            $this->assertSame(0, DB::table('employees')->select('employee_number')->groupBy('employee_number')->havingRaw('count(*) > 1')->count());
            $this->assertSame(0, DB::table('users')->select('email')->groupBy('email')->havingRaw('count(*) > 1')->count());
            $this->assertSame(0, DB::table('event_participants')->select('event_id', 'employee_id')->groupBy('event_id', 'employee_id')->havingRaw('count(*) > 1')->count());
            $this->assertSame(0, DB::table('event_attendances')->select('event_id', 'employee_id')->groupBy('event_id', 'employee_id')->havingRaw('count(*) > 1')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    /** @return array<string, int> */
    private function tableCounts(): array
    {
        return collect([
            'users',
            'institutions',
            'positions',
            'employees',
            'employee_invitations',
            'employee_documents',
            'employee_qr_tokens',
            'events',
            'event_participants',
            'event_attendances',
        ])->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
    }
}
