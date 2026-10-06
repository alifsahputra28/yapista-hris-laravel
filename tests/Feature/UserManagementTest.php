<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_access_user_management_and_see_its_menu(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('users.index', absolute: false))
            ->assertOk()
            ->assertSee('Manajemen User')
            ->assertSee(route('users.create', absolute: false), escape: false);

        foreach (['hr_admin', 'panitia', 'pegawai'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)
                ->get(route('users.index', absolute: false))
                ->assertForbidden();

            $this->actingAs($user)
                ->get($this->dashboardFor($role))
                ->assertOk()
                ->assertDontSee('Manajemen User');
        }
    }

    public function test_index_paginates_with_global_numbers_and_whitelisted_page_size(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'name' => 'Admin Utama']);
        User::factory()->count(20)->create();

        $response = $this->actingAs($admin)
            ->get(route('users.index', ['page' => 2], absolute: false));

        $response->assertOk()->assertSee('16');
        $this->assertSame(15, $response->viewData('users')->perPage());
        $this->assertSame(2, $response->viewData('users')->currentPage());

        $response = $this->actingAs($admin)
            ->get(route('users.index', ['per_page' => 999], absolute: false));

        $this->assertSame(15, $response->viewData('users')->perPage());
    }

    public function test_index_searches_and_filters_users_while_preserving_query_string(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        for ($index = 1; $index <= 26; $index++) {
            User::factory()->create([
                'name' => "Target Panitia {$index}",
                'email' => "target-panitia-{$index}@yapista.test",
                'role' => 'panitia',
                'status' => 'inactive',
            ]);
        }

        User::factory()->create([
            'name' => 'User Diabaikan',
            'role' => 'hr_admin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'search' => 'Target Panitia',
            'role' => 'panitia',
            'status' => 'inactive',
            'per_page' => 25,
        ], absolute: false));

        $response
            ->assertOk()
            ->assertSee('Target Panitia 1')
            ->assertDontSee('User Diabaikan')
            ->assertSee('search=Target%20Panitia', escape: false)
            ->assertSee('role=panitia', escape: false)
            ->assertSee('status=inactive', escape: false)
            ->assertSee('per_page=25', escape: false);

        $this->assertSame(26, $response->viewData('users')->total());
        $this->assertSame(25, $response->viewData('users')->perPage());
    }

    public function test_search_can_match_employee_name_and_nup_without_exposing_sensitive_fields(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = $this->employee([
            'user_id' => $user->id,
            'full_name' => 'Pegawai Khusus Pencarian',
            'employee_number' => '1234567890',
            'nik' => '3201010101010001',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => '1234567890'], absolute: false))
            ->assertOk()
            ->assertSee($employee->full_name)
            ->assertDontSee('3201010101010001');
    }

    public function test_super_admin_can_create_user_and_link_an_available_employee(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $employee = $this->employee();
        $invitation = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'invitation_code' => 'YAPISTA-REG-MANAGED-USER',
            'email' => $employee->email,
            'status' => 'unused',
            'expired_at' => now()->addDay(),
            'created_by' => $admin->id,
        ]);
        $plainPassword = 'PasswordBaru123!';

        $this->actingAs($admin)
            ->post(route('users.store', absolute: false), [
                'name' => '  User Pegawai Baru  ',
                'email' => '  USER.BARU@YAPISTA.TEST ',
                'role' => 'pegawai',
                'employee_id' => $employee->id,
                'password' => $plainPassword,
                'password_confirmation' => $plainPassword,
                'status' => 'active',
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $user = User::where('email', 'user.baru@yapista.test')->firstOrFail();
        $this->assertSame('User Pegawai Baru', $user->name);
        $this->assertSame($user->id, $employee->refresh()->user_id);
        $this->assertSame('revoked', $invitation->refresh()->status);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
        $this->assertNotSame($plainPassword, $user->password);
    }

    public function test_create_rejects_duplicate_email_invalid_role_missing_or_linked_employee(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['email' => 'existing@yapista.test']);
        $linkedUser = User::factory()->create(['role' => 'pegawai']);
        $linkedEmployee = $this->employee(['user_id' => $linkedUser->id]);
        $password = 'PasswordBaru123!';

        $base = [
            'name' => 'User Baru',
            'email' => 'baru@yapista.test',
            'role' => 'pegawai',
            'password' => $password,
            'password_confirmation' => $password,
            'status' => 'active',
        ];

        $this->actingAs($admin)
            ->post(route('users.store', absolute: false), array_merge($base, [
                'email' => 'EXISTING@YAPISTA.TEST',
                'employee_id' => $linkedEmployee->id,
            ]))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->post(route('users.store', absolute: false), array_merge($base, [
                'role' => 'owner',
                'employee_id' => $linkedEmployee->id,
            ]))
            ->assertSessionHasErrors('role');

        $this->actingAs($admin)
            ->post(route('users.store', absolute: false), $base)
            ->assertSessionHasErrors('employee_id');

        $this->actingAs($admin)
            ->post(route('users.store', absolute: false), array_merge($base, [
                'employee_id' => $linkedEmployee->id,
            ]))
            ->assertSessionHasErrors('employee_id');
    }

    public function test_super_admin_can_update_user_and_change_employee_linkage(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $user = User::factory()->create(['role' => 'pegawai']);
        $oldEmployee = $this->employee(['user_id' => $user->id]);
        $newEmployee = $this->employee();

        $this->actingAs($admin)
            ->put(route('users.update', $user, absolute: false), [
                'name' => 'Nama User Diperbarui',
                'email' => 'updated.user@yapista.test',
                'role' => 'pegawai',
                'employee_id' => $newEmployee->id,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('users.index', absolute: false));

        $user->refresh();
        $this->assertSame('Nama User Diperbarui', $user->name);
        $this->assertSame('updated.user@yapista.test', $user->email);
        $this->assertSame('inactive', $user->status);
        $this->assertNull($oldEmployee->refresh()->user_id);
        $this->assertSame($user->id, $newEmployee->refresh()->user_id);
    }

    public function test_super_admin_can_reset_password_and_old_password_stops_working(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create([
            'role' => 'panitia',
            'password' => Hash::make('PasswordLama123!'),
        ]);
        $newPassword = 'PasswordBaru123!';

        $this->actingAs($admin)
            ->patch(route('users.reset-password', $target, absolute: false), [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
                'reset_user_id' => $target->id,
            ])
            ->assertRedirect(route('users.index', absolute: false))
            ->assertSessionHas('success', 'Password berhasil diperbarui.');

        $target->refresh();
        $this->assertTrue(Hash::check($newPassword, $target->password));
        $this->assertNotSame($newPassword, $target->password);

        auth()->logout();

        $this->post(route('login', absolute: false), [
            'email' => $target->email,
            'password' => 'PasswordLama123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login', absolute: false), [
            'email' => $target->email,
            'password' => $newPassword,
        ])->assertRedirect(route('scanner.dashboard', absolute: false));
        $this->assertAuthenticatedAs($target);
    }

    public function test_reset_password_requires_twelve_characters_and_rejects_non_super_admins(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create([
            'role' => 'panitia',
            'password' => Hash::make('PasswordAwal123!'),
        ]);

        $this->actingAs($admin)
            ->patch(route('users.reset-password', $target, absolute: false), [
                'password' => 'Pendek123!',
                'password_confirmation' => 'Pendek123!',
                'reset_user_id' => $target->id,
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('PasswordAwal123!', $target->fresh()->password));

        foreach (['hr_admin', 'panitia', 'pegawai'] as $role) {
            $actor = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($actor)
                ->patch(route('users.reset-password', $target, absolute: false), [
                    'password' => 'PasswordTidakBoleh123!',
                    'password_confirmation' => 'PasswordTidakBoleh123!',
                ])
                ->assertForbidden();
        }
    }

    public function test_deactivate_blocks_login_and_reactivate_allows_login_again(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create([
            'role' => 'panitia',
            'status' => 'active',
            'password' => Hash::make('PasswordTarget123!'),
        ]);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $target, absolute: false))
            ->assertSessionHas('success');

        $this->assertSame('inactive', $target->refresh()->status);
        auth()->logout();

        $this->post(route('login', absolute: false), [
            'email' => $target->email,
            'password' => 'PasswordTarget123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $target, absolute: false))
            ->assertSessionHas('success');

        $this->assertSame('active', $target->refresh()->status);
        auth()->logout();

        $this->post(route('login', absolute: false), [
            'email' => $target->email,
            'password' => 'PasswordTarget123!',
        ])->assertRedirect(route('scanner.dashboard', absolute: false));
        $this->assertAuthenticatedAs($target);
    }

    public function test_inactive_existing_session_is_logged_out_on_the_next_request(): void
    {
        $user = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        $this->actingAs($user);

        $user->update(['status' => 'inactive']);

        $this->get(route('scanner.dashboard', absolute: false))
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_last_active_super_admin_cannot_be_disabled_or_downgraded(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $admin, absolute: false))
            ->assertSessionHasErrors([
                'status' => 'Minimal satu Super Admin aktif harus tersedia.',
            ]);

        $employee = $this->employee();
        $this->actingAs($admin)
            ->put(route('users.update', $admin, absolute: false), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'pegawai',
                'employee_id' => $employee->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors([
                'status' => 'Minimal satu Super Admin aktif harus tersedia.',
            ]);

        $this->assertSame('super_admin', $admin->refresh()->role);
        $this->assertSame('active', $admin->status);
    }

    public function test_super_admin_cannot_disable_or_downgrade_own_account_when_another_admin_exists(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->patch(route('users.toggle-status', $admin, absolute: false))
            ->assertSessionHasErrors([
                'status' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);

        $this->actingAs($admin)
            ->put(route('users.update', $admin, absolute: false), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'hr_admin',
                'employee_id' => null,
                'status' => 'active',
            ])
            ->assertSessionHasErrors([
                'role' => 'Anda tidak dapat menurunkan role akun sendiri.',
            ]);

        $this->assertSame('super_admin', $admin->refresh()->role);
        $this->assertSame('active', $admin->status);
    }

    private function dashboardFor(string $role): string
    {
        return match ($role) {
            'hr_admin' => route('dashboard', absolute: false),
            'panitia' => route('scanner.dashboard', absolute: false),
            'pegawai' => route('pegawai.dashboard', absolute: false),
        };
    }

    /** @param array<string, mixed> $attributes */
    private function employee(array $attributes = []): Employee
    {
        $suffix = str_pad((string) (Employee::query()->count() + 1), 2, '0', STR_PAD_LEFT);
        $institution = Institution::create([
            'name' => "Unit User {$suffix}",
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => "Staf User {$suffix}",
            'type' => 'administratif',
            'status' => 'active',
        ]);

        return Employee::create(array_merge([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => "90000000{$suffix}",
            'full_name' => "Pegawai User {$suffix}",
            'email' => "pegawai-user-{$suffix}@yapista.test",
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $attributes));
    }
}
