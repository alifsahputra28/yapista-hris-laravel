<?php

namespace Tests\Feature\Finalization;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_list_handles_malformed_search_filter_and_pagination_inputs(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        foreach (['', '   ', '%', '_', "'", '"', str_repeat('x', 2_000), 'Nur Aisyah 中文 é'] as $search) {
            $this->actingAs($admin)
                ->get(route('employees.index', [
                    'search' => $search,
                    'institution_id' => 999_999,
                    'position_id' => 999_999,
                    'page' => 'not-a-number',
                ], absolute: false))
                ->assertOk();
        }

        foreach ([0, -1, 999_999] as $page) {
            $this->actingAs($admin)
                ->get(route('employees.index', ['page' => $page], absolute: false))
                ->assertOk();
        }
    }

    public function test_user_controlled_employee_text_is_escaped_in_html(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        [$institution, $position] = $this->masterData();
        $payload = '<script>alert(1)</script><img src=x onerror=alert(1)>';

        Employee::create([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => '7770972001',
            'full_name' => $payload,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ]);

        $this->actingAs($admin)
            ->get(route('employees.index', absolute: false))
            ->assertOk()
            ->assertDontSee($payload, escape: false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false);
    }

    public function test_employee_name_length_boundary_and_mass_assignment_are_enforced(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        [$institution, $position] = $this->masterData();
        $base = [
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => null,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'role' => 'super_admin',
            'verified_by' => 999_999,
            'qr_token' => 'forged-token',
        ];

        $this->actingAs($admin)
            ->post(route('employees.store', absolute: false), $base + [
                'full_name' => str_repeat('A', 255),
                'email' => 'boundary-ok@yapista.test',
                'verification_status' => 'verified',
            ])
            ->assertRedirect(route('employees.index', absolute: false));

        $employee = Employee::where('email', 'boundary-ok@yapista.test')->firstOrFail();
        $this->assertSame('draft', $employee->verification_status);
        $this->assertNull($employee->verified_by);
        $this->assertFalse($employee->activeQrToken()->exists());

        $this->actingAs($admin)
            ->post(route('employees.store', absolute: false), $base + [
                'full_name' => str_repeat('B', 256),
                'email' => 'boundary-fail@yapista.test',
            ])
            ->assertSessionHasErrors('full_name');

        $this->assertDatabaseMissing('employees', ['email' => 'boundary-fail@yapista.test']);
    }

    /** @return array{Institution, Position} */
    private function masterData(): array
    {
        $institution = Institution::create(['name' => 'Unit Edge Case', 'status' => 'active']);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Jabatan Edge Case',
            'status' => 'active',
        ]);

        return [$institution, $position];
    }
}
