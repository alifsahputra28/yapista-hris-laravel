<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_unique_indexes_are_present(): void
    {
        $this->assertIndex('institutions', 'institutions_name_unique', ['name'], true);
        $this->assertIndex('positions', 'positions_institution_name_unique', ['institution_id', 'name'], true);
        $this->assertIndex('employees', 'employees_user_id_unique', ['user_id'], true);
        $this->assertIndex('employees', 'employees_employee_number_unique', ['employee_number'], true);
        $this->assertIndex('employees', 'employees_nik_lookup_unique', ['nik_lookup'], true);
        $this->assertIndex('employee_administrative_details', 'employee_administrative_details_employee_id_unique', ['employee_id'], true);
        $this->assertIndex('event_participants', 'event_participants_event_id_employee_id_unique', ['event_id', 'employee_id'], true);
        $this->assertIndex('event_attendances', 'event_attendances_event_id_employee_id_unique', ['event_id', 'employee_id'], true);
    }

    public function test_database_rejects_duplicate_institution_names(): void
    {
        Institution::create(['name' => 'Unit Unik', 'status' => 'active']);

        $this->expectException(QueryException::class);
        Institution::create(['name' => 'Unit Unik', 'status' => 'active']);
    }

    public function test_position_name_is_unique_per_unit_but_may_repeat_across_units(): void
    {
        $first = Institution::create(['name' => 'Unit Pertama', 'status' => 'active']);
        $second = Institution::create(['name' => 'Unit Kedua', 'status' => 'active']);
        Position::create(['institution_id' => $first->id, 'name' => 'Guru', 'status' => 'active']);
        Position::create(['institution_id' => $second->id, 'name' => 'Guru', 'status' => 'active']);

        $this->assertSame(2, Position::query()->where('name', 'Guru')->count());

        $this->expectException(QueryException::class);
        Position::create(['institution_id' => $first->id, 'name' => 'Guru', 'status' => 'active']);
    }

    public function test_position_must_reference_an_institution(): void
    {
        $this->expectException(QueryException::class);
        Position::create(['institution_id' => null, 'name' => 'Tanpa Unit', 'status' => 'active']);
    }

    public function test_one_user_cannot_be_linked_to_multiple_employees(): void
    {
        [$institution, $position] = $this->masterData('Unit Akun');
        $user = User::factory()->create(['role' => 'pegawai']);
        Employee::create($this->employeeData($institution, $position, '7770982001', ['user_id' => $user->id]));

        $this->expectException(QueryException::class);
        Employee::create($this->employeeData($institution, $position, '7770982002', ['user_id' => $user->id]));
    }

    public function test_integrity_repair_dry_run_does_not_change_data(): void
    {
        $fixtures = $this->repairFixtures();

        $this->assertSame(0, Artisan::call('employees:repair-integrity', ['--dry-run' => true]));
        $this->assertStringContainsString('Dry run only', Artisan::output());

        $this->assertSame('draft', $fixtures['validDraft']->fresh()->verification_status);
        $this->assertNull($fixtures['legacyVerified']->fresh()->verified_at);
        $this->assertTrue($fixtures['staleQrEmployee']->activeQrToken()->exists());
        $this->assertNotNull($fixtures['manualAttendance']->fresh()->qr_token_id);
    }

    public function test_integrity_repair_is_safe_and_idempotent(): void
    {
        $fixtures = $this->repairFixtures();

        $this->assertSame(0, Artisan::call('employees:repair-integrity', ['--commit' => true]));

        $validDraft = $fixtures['validDraft']->fresh();
        $legacyVerified = $fixtures['legacyVerified']->fresh();
        $staleQrEmployee = $fixtures['staleQrEmployee']->fresh();

        $this->assertSame('verified', $validDraft->verification_status);
        $this->assertNotNull($validDraft->verified_at);
        $this->assertNull($validDraft->verified_by);
        $this->assertTrue($validDraft->activeQrToken()->exists());
        $this->assertNotNull($legacyVerified->verified_at);
        $this->assertNull($legacyVerified->verified_by);
        $this->assertFalse($staleQrEmployee->activeQrToken()->exists());
        $this->assertSame('draft', $fixtures['newEmployee']->fresh()->verification_status);
        $this->assertNull($fixtures['newEmployee']->fresh()->employee_number);
        $this->assertNull($fixtures['manualAttendance']->fresh()->qr_token_id);

        $tokenId = $validDraft->activeQrToken()->value('id');
        $this->assertSame(0, Artisan::call('employees:repair-integrity', ['--commit' => true]));
        $this->assertSame($tokenId, $validDraft->activeQrToken()->value('id'));
        $this->assertStringContainsString('Integrity repair completed and verified', Artisan::output());
    }

    public function test_event_with_attendance_history_cannot_be_deleted(): void
    {
        [$institution, $position] = $this->masterData('Unit Histori');
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $employee = Employee::create($this->employeeData($institution, $position, '7770982010'));
        $event = $this->eventWithAttendance($employee, $admin, 'Kegiatan Berhistori');

        $this->actingAs($admin)
            ->delete(route('events.destroy', $event, absolute: false))
            ->assertRedirect(route('events.index', absolute: false))
            ->assertSessionHas('error', 'Kegiatan tidak dapat dihapus karena sudah memiliki riwayat kehadiran.');

        $this->assertDatabaseHas('events', ['id' => $event->id]);
        $this->assertDatabaseHas('event_attendances', ['event_id' => $event->id]);

        $this->expectException(QueryException::class);
        DB::table('events')->where('id', $event->id)->delete();
    }

    /** @return array<string, Employee|EventAttendance> */
    private function repairFixtures(): array
    {
        [$institution, $position] = $this->masterData('Unit Repair');
        $validDraft = Employee::create($this->employeeData($institution, $position, '7770982021', [
            'verification_status' => 'draft',
        ]));
        $legacyVerified = Employee::create($this->employeeData($institution, $position, '7770982022', [
            'verified_at' => null,
            'verified_by' => null,
        ]));
        $staleQrEmployee = Employee::create($this->employeeData($institution, $position, '7770982023'));
        $newEmployee = Employee::create($this->employeeData($institution, $position, null, [
            'verification_status' => 'draft',
        ]));

        $tokenService = app(EmployeeQrTokenService::class);
        $legacyToken = $tokenService->generate($legacyVerified);
        $tokenService->generate($staleQrEmployee);
        $staleQrEmployee->update(['employment_status' => 'nonaktif']);

        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $event = Event::create([
            'name' => 'Kegiatan Repair',
            'event_date' => now()->toDateString(),
            'target_type' => 'selected',
            'status' => 'cancelled',
            'created_by' => $admin->id,
        ]);
        EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $legacyVerified->id,
            'participant_status' => 'confirmed',
        ]);
        $manualAttendance = EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $legacyVerified->id,
            'qr_token_id' => $legacyToken->id,
            'scanned_by' => $admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'manual',
            'scan_method' => 'manual',
        ]);

        return compact('validDraft', 'legacyVerified', 'staleQrEmployee', 'newEmployee', 'manualAttendance');
    }

    private function eventWithAttendance(Employee $employee, User $admin, string $name): Event
    {
        $event = Event::create([
            'name' => $name,
            'event_date' => now()->toDateString(),
            'target_type' => 'selected',
            'status' => 'cancelled',
            'created_by' => $admin->id,
        ]);
        EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'participant_status' => 'confirmed',
        ]);
        EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'scanned_by' => $admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'manual',
            'scan_method' => 'manual',
        ]);

        return $event;
    }

    /** @return array{Institution, Position} */
    private function masterData(string $name): array
    {
        $institution = Institution::create(['name' => $name, 'status' => 'active']);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Jabatan '.$name,
            'status' => 'active',
        ]);

        return [$institution, $position];
    }

    /** @param array<string, mixed> $overrides */
    private function employeeData(Institution $institution, Position $position, ?string $number, array $overrides = []): array
    {
        return array_merge([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => $number,
            'full_name' => 'Pegawai Integritas '.($number ?? 'Baru'),
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => $number === null ? 'draft' : 'verified',
        ], $overrides);
    }

    /** @param list<string> $columns */
    private function assertIndex(string $table, string $name, array $columns, bool $unique): void
    {
        $index = collect(Schema::getIndexes($table))->firstWhere('name', $name);

        $this->assertNotNull($index, "Index {$name} tidak ditemukan pada {$table}.");
        $this->assertSame($columns, $index['columns']);
        $this->assertSame($unique, $index['unique']);
    }
}
