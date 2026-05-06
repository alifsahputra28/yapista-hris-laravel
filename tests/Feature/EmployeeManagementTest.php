<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_employee_data(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        [$institution, $position] = $this->institutionAndPosition();

        $this->actingAs($admin)
            ->get('/employees')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/employees', [
                'full_name' => 'Ahmad Fauzi',
                'institution_id' => $institution->id,
                'position_id' => $position->id,
                'email' => 'ahmad.fauzi@yapista.test',
                'nik' => '3201010101010001',
                'gender' => 'male',
                'birth_place' => 'Bekasi',
                'birth_date' => '1990-01-01',
                'phone' => '081234567890',
                'address' => 'Jl. Pendidikan',
                'employee_type' => 'guru',
                'employment_status' => 'aktif',
                'join_date' => '2024-07-01',
                'photo' => UploadedFile::fake()->image('pegawai.jpg'),
            ])
            ->assertRedirect(route('employees.index', absolute: false));

        $employee = Employee::where('email', 'ahmad.fauzi@yapista.test')->firstOrFail();

        $this->assertSame('draft', $employee->verification_status);
        $this->assertNotNull($employee->photo);
        Storage::disk('public')->assertExists($employee->photo);

        $this->actingAs($admin)
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertSee('Ahmad Fauzi');

        $this->actingAs($admin)
            ->put("/employees/{$employee->id}", [
                'full_name' => 'Ahmad Fauzi Update',
                'institution_id' => $institution->id,
                'position_id' => $position->id,
                'email' => 'ahmad.fauzi@yapista.test',
                'nik' => '3201010101010001',
                'gender' => 'male',
                'birth_place' => 'Bekasi',
                'birth_date' => '1990-01-01',
                'phone' => '081234567891',
                'address' => 'Jl. Pendidikan 2',
                'employee_type' => 'guru',
                'employment_status' => 'kontrak',
                'join_date' => '2024-07-01',
            ])
            ->assertRedirect(route('employees.index', absolute: false));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Ahmad Fauzi Update',
            'employment_status' => 'kontrak',
        ]);

        $this->actingAs($admin)
            ->delete("/employees/{$employee->id}")
            ->assertRedirect(route('employees.index', absolute: false));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'employment_status' => 'nonaktif',
        ]);
    }

    public function test_employee_index_can_search_and_filter(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        [$institution, $position] = $this->institutionAndPosition();
        Employee::create([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Siti Aminah',
            'email' => 'siti@yapista.test',
            'phone' => '0812',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->get('/employees?search=Siti&institution_id='.$institution->id.'&position_id='.$position->id.'&verification_status=draft&employment_status=aktif')
            ->assertOk()
            ->assertSee('Siti Aminah');
    }

    public function test_non_admin_roles_can_not_access_employee_data(): void
    {
        foreach (['panitia', 'pegawai'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get('/employees')
                ->assertForbidden();
        }
    }

    /**
     * @return array{Institution, Position}
     */
    private function institutionAndPosition(): array
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

        return [$institution, $position];
    }
}
