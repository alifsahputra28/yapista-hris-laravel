<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAccountProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_nup_login_account_without_email(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $employee = $this->employee(['employee_number' => '0012345678']);
        $password = 'PasswordNup123!';

        $this->actingAs($admin)
            ->get(route('employees.account.create', $employee, absolute: false))
            ->assertOk()
            ->assertSee('NUP')
            ->assertSee('Email Login');

        $this->actingAs($admin)
            ->post(route('employees.account.store', $employee, absolute: false), [
                'role' => 'pegawai',
                'email' => '',
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertRedirect(route('employees.show', $employee, absolute: false));

        $employee->refresh();
        $user = User::findOrFail($employee->user_id);

        $this->assertNull($user->email);
        $this->assertSame($user->id, $employee->user_id);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertNotSame($password, $user->password);

        auth()->logout();
        $this->post(route('login', absolute: false), [
            'login' => '0012345678',
            'password' => $password,
        ])->assertRedirect(route('pegawai.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_only_super_admin_can_open_account_provisioning_flow(): void
    {
        $employee = $this->employee(['employee_number' => '0012345678']);

        foreach (['hr_admin', 'panitia', 'pegawai'] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor)
                ->get(route('employees.account.create', $employee, absolute: false))
                ->assertForbidden();

            $this->actingAs($actor)
                ->post(route('employees.account.store', $employee, absolute: false), [])
                ->assertForbidden();
        }
    }

    public function test_employee_without_nup_requires_email_for_account_provisioning(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $employee = $this->employee(['employee_number' => null]);
        $password = 'PasswordNup123!';

        $this->actingAs($admin)
            ->post(route('employees.account.store', $employee, absolute: false), [
                'role' => 'pegawai',
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertSessionHasErrors('email');

        $this->assertNull($employee->refresh()->user_id);
    }

    public function test_account_provisioning_rejects_second_account_and_duplicate_email(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $first = $this->employee(['employee_number' => '0012345678']);
        $second = $this->employee(['employee_number' => '0012345679']);
        $existing = User::factory()->create(['email' => 'existing@yapista.test']);
        $password = 'PasswordNup123!';

        $this->actingAs($admin)
            ->post(route('employees.account.store', $first, absolute: false), [
                'role' => 'pegawai',
                'email' => 'EXISTING@YAPISTA.TEST',
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertSessionHasErrors('email');

        $this->assertNull($first->refresh()->user_id);

        $this->actingAs($admin)
            ->post(route('employees.account.store', $first, absolute: false), [
                'role' => 'pegawai',
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertRedirect(route('employees.show', $first, absolute: false));

        $this->actingAs($admin)
            ->post(route('employees.account.store', $first, absolute: false), [
                'role' => 'pegawai',
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertSessionHasErrors('employee');

        $this->assertSame($existing->id, User::where('email', 'existing@yapista.test')->value('id'));
        $this->assertNull($second->refresh()->user_id);
    }

    public function test_email_login_remains_backward_compatible_and_inactive_accounts_are_rejected(): void
    {
        $password = 'PasswordEmail123!';
        $user = User::factory()->create([
            'email' => 'legacy.login@yapista.test',
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->post(route('login', absolute: false), [
            'email' => 'LEGACY.LOGIN@YAPISTA.TEST',
            'password' => $password,
        ])->assertRedirect(route('dashboard', absolute: false));

        auth()->logout();
        $user->update(['status' => 'inactive']);

        $this->post(route('login', absolute: false), [
            'login' => 'legacy.login@yapista.test',
            'password' => $password,
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_nup_login_rejects_unknown_nup_and_wrong_password_generically(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $employee = $this->employee(['employee_number' => '0012345678']);
        $password = 'PasswordNup123!';

        $this->actingAs($admin)
            ->post(route('employees.account.store', $employee, absolute: false), [
                'role' => 'pegawai',
                'password' => $password,
                'password_confirmation' => $password,
            ]);

        auth()->logout();

        $this->post(route('login', absolute: false), [
            'login' => '0099999999',
            'password' => $password,
        ])->assertSessionHasErrors(['login' => 'NUP/email atau password tidak sesuai.']);

        $this->post(route('login', absolute: false), [
            'login' => '0012345678',
            'password' => 'PasswordSalah123!',
        ])->assertSessionHasErrors(['login' => 'NUP/email atau password tidak sesuai.']);
    }

    /** @param array<string, mixed> $attributes */
    private function employee(array $attributes = []): Employee
    {
        $suffix = str_pad((string) (Employee::query()->count() + 1), 2, '0', STR_PAD_LEFT);
        $institution = Institution::create([
            'name' => "Unit Account {$suffix}",
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => "Staf Account {$suffix}",
            'type' => 'administratif',
            'status' => 'active',
        ]);

        return Employee::create(array_merge([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => '9000000001',
            'full_name' => "Pegawai Account {$suffix}",
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $attributes));
    }
}
