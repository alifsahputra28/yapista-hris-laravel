<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_and_revoke_employee_invitation(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('employees.invitations.generate', $employee, absolute: false))
            ->assertRedirect(route('invitations.index', absolute: false));

        $invitation = EmployeeInvitation::where('employee_id', $employee->id)->firstOrFail();

        $this->assertStringStartsWith('YAPISTA-REG-', $invitation->invitation_code);
        $this->assertTrue($invitation->isValid());
        $this->assertSame($admin->id, $invitation->created_by);

        $this->actingAs($admin)
            ->get('/invitations')
            ->assertOk()
            ->assertSee($invitation->invitation_code);

        $this->actingAs($admin)
            ->patch(route('invitations.revoke', $invitation, absolute: false))
            ->assertRedirect(route('invitations.index', absolute: false));

        $this->assertSame('revoked', $invitation->refresh()->status);
    }

    public function test_admin_can_not_generate_new_invitation_when_active_invitation_exists(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('employees.invitations.generate', $employee, absolute: false));

        $this->actingAs($admin)
            ->post(route('employees.invitations.generate', $employee, absolute: false))
            ->assertRedirect(route('invitations.index', absolute: false));

        $this->assertSame(1, EmployeeInvitation::where('employee_id', $employee->id)->count());
    }

    public function test_employee_can_register_using_valid_invitation_code(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $employee = $this->employee();
        $invitation = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'invitation_code' => 'YAPISTA-REG-ABC123',
            'email' => $employee->email,
            'phone' => $employee->phone,
            'status' => 'unused',
            'expired_at' => now()->addDays(14),
            'created_by' => $admin->id,
        ]);

        $this->get(route('invitation.register.show', $invitation->invitation_code, absolute: false))
            ->assertOk()
            ->assertSee($employee->full_name);

        $response = $this->post(route('invitation.register.store', $invitation->invitation_code, absolute: false), [
            'name' => $employee->full_name,
            'email' => 'ahmad.account@yapista.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('pegawai.dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'ahmad.account@yapista.test')->firstOrFail();
        $this->assertSame('pegawai', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertSame($user->id, $employee->refresh()->user_id);
        $this->assertSame('used', $invitation->refresh()->status);
        $this->assertNotNull($invitation->used_at);
    }

    public function test_invalid_invitation_code_can_not_be_used(): void
    {
        $employee = $this->employee();
        $invitation = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'invitation_code' => 'YAPISTA-REG-EXPIRED',
            'status' => 'unused',
            'expired_at' => now()->subDay(),
        ]);

        $this->get(route('invitation.register.show', $invitation->invitation_code, absolute: false))
            ->assertOk()
            ->assertSee('kedaluwarsa');

        $this->post(route('invitation.register.store', $invitation->invitation_code, absolute: false), [
            'name' => 'Expired User',
            'email' => 'expired@yapista.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertSessionHasErrors('invitation');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'expired@yapista.test',
        ]);
    }

    public function test_non_admin_roles_can_not_access_invitation_admin_pages(): void
    {
        foreach (['panitia', 'pegawai'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get('/invitations')
                ->assertForbidden();
        }
    }

    private function employee(): Employee
    {
        $institution = Institution::create([
            'name' => 'SMK Ibnu Sina',
            'level' => 'SMK',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Guru',
            'type' => 'fungsional',
            'status' => 'active',
        ]);

        return Employee::create([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Ahmad Fauzi',
            'email' => 'ahmad.demo@yapista.test',
            'phone' => '081234567890',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ]);
    }
}
