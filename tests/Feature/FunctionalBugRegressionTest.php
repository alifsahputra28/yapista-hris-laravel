<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunctionalBugRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_create_uses_nup_to_distinguish_existing_and_new_employees(): void
    {
        [$institution, $position] = $this->masterData('Unit Existing');

        $this->actingAs($this->admin)
            ->post(route('employees.store', absolute: false), $this->employeePayload($institution, $position, [
                'employee_number' => '7770981001',
                'email' => 'existing-created@yapista.test',
            ]))
            ->assertRedirect(route('employees.index', absolute: false));

        $existing = Employee::query()->where('email', 'existing-created@yapista.test')->firstOrFail();
        $this->assertSame('verified', $existing->verification_status);
        $this->assertSame($this->admin->id, $existing->verified_by);
        $this->assertNotNull($existing->verified_at);
        $this->assertTrue($existing->activeQrToken()->exists());

        $this->actingAs($this->admin)
            ->post(route('employees.store', absolute: false), $this->employeePayload($institution, $position, [
                'employee_number' => null,
                'email' => 'new-created@yapista.test',
            ]))
            ->assertRedirect(route('employees.index', absolute: false));

        $new = Employee::query()->where('email', 'new-created@yapista.test')->firstOrFail();
        $this->assertSame('draft', $new->verification_status);
        $this->assertNull($new->verified_by);
        $this->assertNull($new->verified_at);
        $this->assertFalse($new->activeQrToken()->exists());
    }

    public function test_adding_valid_nup_to_draft_employee_marks_it_verified_and_creates_qr(): void
    {
        [$institution, $position] = $this->masterData('Unit Promote');
        $employee = Employee::create($this->employeePayload($institution, $position, [
            'employee_number' => null,
            'email' => 'promoted@yapista.test',
            'verification_status' => 'draft',
        ]));

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee, absolute: false), $this->employeePayload($institution, $position, [
                'employee_number' => '7770981002',
                'email' => $employee->email,
            ]))
            ->assertRedirect(route('employees.index', absolute: false));

        $employee->refresh();
        $this->assertSame('verified', $employee->verification_status);
        $this->assertSame($this->admin->id, $employee->verified_by);
        $this->assertNotNull($employee->verified_at);
        $this->assertTrue($employee->activeQrToken()->exists());
    }

    public function test_employee_position_must_belong_to_selected_institution(): void
    {
        [$institutionA] = $this->masterData('Unit A');
        [, $positionB] = $this->masterData('Unit B');

        $this->actingAs($this->admin)
            ->post(route('employees.store', absolute: false), $this->employeePayload($institutionA, $positionB, [
                'email' => 'cross-unit@yapista.test',
            ]))
            ->assertSessionHasErrors('position_id');

        $this->assertDatabaseMissing('employees', ['email' => 'cross-unit@yapista.test']);
    }

    public function test_duplicate_master_data_is_rejected_without_overwriting_existing_records(): void
    {
        [$institution, $position] = $this->masterData('Unit Duplikat');

        $this->actingAs($this->admin)
            ->post(route('institutions.store', absolute: false), [
                'name' => $institution->name,
                'level' => 'Unit',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post(route('positions.store', absolute: false), [
                'institution_id' => $institution->id,
                'name' => $position->name,
                'type' => 'administratif',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Institution::query()->where('name', $institution->name)->count());
        $this->assertSame(1, Position::query()
            ->where('institution_id', $institution->id)
            ->where('name', $position->name)
            ->count());
    }

    public function test_master_data_in_use_returns_business_error_instead_of_database_exception(): void
    {
        [$institution, $position] = $this->masterData('Unit Digunakan');
        Employee::create($this->employeePayload($institution, $position, [
            'employee_number' => '7770981003',
            'email' => 'master-in-use@yapista.test',
            'verification_status' => 'verified',
        ]));

        $this->actingAs($this->admin)
            ->delete(route('positions.destroy', $position, absolute: false))
            ->assertRedirect(route('positions.index', absolute: false))
            ->assertSessionHas('error');

        $this->actingAs($this->admin)
            ->delete(route('institutions.destroy', $institution, absolute: false))
            ->assertRedirect(route('institutions.index', absolute: false))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('positions', ['id' => $position->id]);
        $this->assertDatabaseHas('institutions', ['id' => $institution->id]);
    }

    public function test_application_uses_yapista_local_timezone_for_operational_timestamps(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
    }

    /** @return array{Institution, Position} */
    private function masterData(string $institutionName): array
    {
        $institution = Institution::create([
            'name' => $institutionName,
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Jabatan '.$institutionName,
            'type' => 'administratif',
            'status' => 'active',
        ]);

        return [$institution, $position];
    }

    /** @param array<string, mixed> $overrides */
    private function employeePayload(Institution $institution, Position $position, array $overrides = []): array
    {
        return array_merge([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => null,
            'full_name' => 'Pegawai Functional Audit',
            'email' => 'functional-audit@yapista.test',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $overrides);
    }
}
