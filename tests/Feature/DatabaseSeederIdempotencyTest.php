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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_idempotent_and_preserves_onboarding_data(): void
    {
        Carbon::setTestNow('2026-08-03 09:00:00');

        try {
            $this->seed(DatabaseSeeder::class);

            $budi = Employee::where('employee_number', '7770923824')->firstOrFail();
            $budiUser = $budi->user()->firstOrFail();
            $originalPassword = $budiUser->password;
            $budi->update([
                'nik' => '2171011603880003',
                'phone' => '081277709003',
                'photo' => 'employees/photos/existing.jpg',
            ]);
            $existingDocument = EmployeeDocument::create([
                'employee_id' => $budi->id,
                'document_type' => 'ktp',
                'document_slot' => 'primary',
                'file_path' => 'employee-documents/'.$budi->id.'/existing.pdf',
                'original_name' => 'existing.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'status' => 'pending',
                'uploaded_at' => now(),
            ]);

            $counts = $this->tableCounts();
            $this->assertSame([
                'users' => 15,
                'institutions' => 7,
                'positions' => 37,
                'employees' => 12,
                'employee_invitations' => 0,
                'employee_documents' => 1,
                'employee_qr_tokens' => 0,
                'events' => 5,
                'event_participants' => 0,
                'event_attendances' => 0,
            ], $counts);
            $eventId = Event::where('name', 'Rapat Koordinasi Yayasan')->firstOrFail()->id;

            Carbon::setTestNow('2026-08-10 09:00:00');
            $this->seed(DatabaseSeeder::class);

            $this->assertSame($counts, $this->tableCounts());
            $this->assertSame($eventId, Event::where('name', 'Rapat Koordinasi Yayasan')->value('id'));
            $this->assertSame('2026-08-10', Event::where('name', 'Rapat Koordinasi Yayasan')->firstOrFail()->event_date->toDateString());

            $budi->refresh();
            $this->assertSame($budiUser->id, $budi->user_id);
            $this->assertSame('2171011603880003', $budi->nik);
            $this->assertSame('081277709003', $budi->phone);
            $this->assertSame('employees/photos/existing.jpg', $budi->photo);
            $this->assertSame($originalPassword, $budiUser->fresh()->password);
            $this->assertDatabaseHas('employee_documents', ['id' => $existingDocument->id]);

            $this->assertSame(0, Employee::query()->whereNotNull('nup')->count());
            $this->assertSame(0, Employee::query()->whereNotNull('foundation_registry_number')->count());
            $this->assertSame(0, DB::table('employees')->select('employee_number')->groupBy('employee_number')->havingRaw('count(*) > 1')->count());
            $this->assertSame(0, DB::table('users')->select('email')->groupBy('email')->havingRaw('count(*) > 1')->count());
            $this->assertTrue(Hash::check('password', User::where('email', 'pegawai@yapista.test')->firstOrFail()->password));
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
