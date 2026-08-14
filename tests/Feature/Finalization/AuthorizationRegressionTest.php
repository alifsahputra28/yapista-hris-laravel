<?php

namespace Tests\Feature\Finalization;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_every_protected_application_area(): void
    {
        $routes = [
            route('dashboard', absolute: false),
            route('employees.index', absolute: false),
            route('verifications.index', absolute: false),
            route('invitations.index', absolute: false),
            route('profile.edit', absolute: false),
            route('pegawai.dashboard', absolute: false),
            route('pegawai.documents.index', absolute: false),
            route('events.index', absolute: false),
            route('scanner.dashboard', absolute: false),
            route('reports.employees', absolute: false),
            route('employees.import.template', absolute: false),
        ];

        foreach ($routes as $uri) {
            $this->get($uri)->assertRedirect(route('login', absolute: false));
        }
    }

    public function test_employee_cannot_access_admin_hr_panitia_or_import_areas(): void
    {
        [$user] = $this->employeeUser();

        foreach ([
            route('dashboard', absolute: false),
            route('employees.index', absolute: false),
            route('verifications.index', absolute: false),
            route('invitations.index', absolute: false),
            route('events.index', absolute: false),
            route('scanner.dashboard', absolute: false),
            route('reports.employees', absolute: false),
            route('employees.import.template', absolute: false),
        ] as $uri) {
            $this->actingAs($user)->get($uri)->assertForbidden();
        }
    }

    public function test_panitia_cannot_access_employee_administration_or_sensitive_routes(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        [, $employee] = $this->employeeUser();
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/authorization.pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        foreach ([
            route('employees.index', absolute: false),
            route('verifications.index', absolute: false),
            route('invitations.index', absolute: false),
            route('reports.employees', absolute: false),
            route('employees.import.template', absolute: false),
            route('employee-documents.view', $document, absolute: false),
        ] as $uri) {
            $this->actingAs($panitia)->get($uri)->assertForbidden();
        }

        $this->actingAs($panitia)
            ->post(route('employees.id-card.qr.regenerate', $employee, absolute: false))
            ->assertForbidden();

        $this->actingAs($panitia)
            ->post(route('employees.nik-search', absolute: false), ['nik' => '3201010101010001'])
            ->assertForbidden();
    }

    public function test_hr_and_super_admin_retain_access_to_administration_routes(): void
    {
        foreach (['hr_admin', 'super_admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)->get(route('dashboard', absolute: false))->assertOk();
            $this->actingAs($user)->get(route('employees.index', absolute: false))->assertOk();
            $this->actingAs($user)->get(route('verifications.index', absolute: false))->assertOk();
            $this->actingAs($user)->get(route('reports.employees', absolute: false))->assertOk();
        }
    }

    public function test_each_role_keeps_only_its_expected_landing_area(): void
    {
        $expectations = [
            'super_admin' => route('dashboard', absolute: false),
            'hr_admin' => route('dashboard', absolute: false),
            'panitia' => route('scanner.dashboard', absolute: false),
            'pegawai' => route('pegawai.dashboard', absolute: false),
        ];

        foreach ($expectations as $role => $destination) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)->get('/')->assertRedirect($destination);
            auth()->logout();
        }
    }

    public function test_critical_mutation_routes_keep_web_csrf_middleware_and_forms_render_tokens(): void
    {
        foreach ([
            'employees.store',
            'employees.import.store',
            'verifications.approve',
            'events.store',
            'events.scan',
            'pegawai.documents.store',
            'pegawai.profile.update',
        ] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route {$routeName} tidak ditemukan.");
            $this->assertContains('web', $route->gatherMiddleware());
        }

        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('employees.create', absolute: false))
            ->assertOk()
            ->assertSee('name="_token"', escape: false);

        $this->actingAs($admin)
            ->get(route('employees.index', absolute: false))
            ->assertOk()
            ->assertSee('name="_token"', escape: false);

        $this->actingAs($admin)
            ->get(route('events.create', absolute: false))
            ->assertOk()
            ->assertSee('name="_token"', escape: false);
    }

    /** @return array{User, Employee} */
    private function employeeUser(): array
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Unit Authorization'],
            ['status' => 'active']
        );
        $position = Position::firstOrCreate(
            ['institution_id' => $institution->id, 'name' => 'Staf Authorization'],
            ['status' => 'active']
        );
        $user = User::factory()->create(['role' => 'pegawai', 'status' => 'active']);
        $employee = Employee::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => '7770971001',
            'full_name' => 'Pegawai Authorization',
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ]);

        return [$user, $employee];
    }
}
