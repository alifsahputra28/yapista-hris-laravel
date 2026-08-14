<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeECardTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private Position $position;

    private User $admin;

    private EmployeeQrTokenService $tokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMK Ibnu Sina',
            'level' => 'SMK',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Guru Produktif',
            'type' => 'pendidik',
            'status' => 'active',
        ]);
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        $this->tokens = app(EmployeeQrTokenService::class);
    }

    public function test_employee_with_valid_nup_and_active_qr_sees_dynamic_e_card(): void
    {
        [$user, $employee] = $this->employeeUser(['nik' => '3201010101010001']);
        $token = $this->tokens->generate($employee, $this->admin);
        $payload = $this->tokens->payloadFor($token);

        $response = $this->actingAs($user)
            ->get(route('pegawai.id-card.show', absolute: false));

        $response
            ->assertOk()
            ->assertViewHas('isReadyForIdCard', true)
            ->assertSee('employee-e-card', escape: false)
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Guru Produktif')
            ->assertSee('SMK Ibnu Sina')
            ->assertSee('7770923822')
            ->assertSee('Aktif')
            ->assertSee('<svg', escape: false)
            ->assertSee('Pindai QR Code untuk absensi kegiatan')
            ->assertDontSee($token->token_encrypted)
            ->assertDontSee('3201010101010001');

        $this->assertStringNotContainsString($employee->employee_number, $payload);
        $this->assertStringNotContainsString((string) $employee->nik, $payload);
    }

    public function test_e_card_uses_employee_photo_and_safe_local_fallback(): void
    {
        [$user, $employee] = $this->employeeUser(['photo' => 'employees/photos/ahmad.jpg']);
        $this->tokens->generate($employee, $this->admin);

        $this->actingAs($user)
            ->get(route('pegawai.id-card.show', absolute: false))
            ->assertOk()
            ->assertSee(route('employees.photo', $employee), escape: false)
            ->assertSee(asset('assets/images/user/avatar-2.jpg'), escape: false);

        [$fallbackUser, $fallbackEmployee] = $this->employeeUser([
            'employee_number' => '7770923823',
            'full_name' => 'Pegawai Tanpa Foto',
            'email' => 'tanpa-foto@yapista.test',
            'photo' => null,
        ]);
        $this->tokens->generate($fallbackEmployee, $this->admin);

        $this->actingAs($fallbackUser)
            ->get(route('pegawai.id-card.show', absolute: false))
            ->assertOk()
            ->assertSee(asset('assets/images/user/avatar-2.jpg'), escape: false)
            ->assertSee('Foto Pegawai Tanpa Foto');
    }

    public function test_employee_without_nup_or_active_qr_sees_safe_unavailable_state(): void
    {
        [$withoutNupUser] = $this->employeeUser([
            'employee_number' => null,
            'email' => 'tanpa-nup@yapista.test',
        ]);

        $this->actingAs($withoutNupUser)
            ->get(route('pegawai.id-card.show', absolute: false))
            ->assertOk()
            ->assertViewHas('isReadyForIdCard', false)
            ->assertSee('ID Card belum tersedia.')
            ->assertDontSee('<article class="employee-e-card"', escape: false);

        [$withoutQrUser] = $this->employeeUser([
            'employee_number' => '7770923824',
            'email' => 'tanpa-qr@yapista.test',
        ]);

        $this->actingAs($withoutQrUser)
            ->get(route('pegawai.id-card.show', absolute: false))
            ->assertOk()
            ->assertViewHas('isReadyForIdCard', false)
            ->assertSee('QR Code belum tersedia. Silakan hubungi HR/Admin.')
            ->assertSee('ID Card belum tersedia.')
            ->assertDontSee('<article class="employee-e-card"', escape: false);
    }

    public function test_self_service_e_card_is_owned_and_protected_by_employee_role(): void
    {
        [$owner, $employee] = $this->employeeUser();
        $this->tokens->generate($employee, $this->admin);

        $this->get(route('pegawai.id-card.show', absolute: false))
            ->assertRedirect(route('login', absolute: false));

        $otherEmployee = User::factory()->create([
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $this->actingAs($otherEmployee)
            ->get(route('employees.id-card.show', $employee, absolute: false))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('pegawai.id-card.show', absolute: false))
            ->assertOk()
            ->assertSee('Ahmad Fauzi');
    }

    public function test_admin_preview_reuses_the_same_e_card_component(): void
    {
        [, $employee] = $this->employeeUser();
        $token = $this->tokens->generate($employee, $this->admin);

        $this->actingAs($this->admin)
            ->get(route('employees.id-card.show', $employee, absolute: false))
            ->assertOk()
            ->assertViewHas('isReadyForIdCard', true)
            ->assertSee('employee-e-card', escape: false)
            ->assertSee('Ahmad Fauzi')
            ->assertDontSee('Informasi Pegawai')
            ->assertDontSee($token->token_encrypted);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{User, Employee}
     */
    private function employeeUser(array $overrides = []): array
    {
        $user = User::factory()->create([
            'name' => $overrides['full_name'] ?? 'Ahmad Fauzi',
            'email' => $overrides['email'] ?? 'ahmad-fauzi@yapista.test',
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'employee_number' => '7770923822',
            'full_name' => 'Ahmad Fauzi',
            'email' => $user->email,
            'nik' => null,
            'employee_type' => 'pendidik',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
            'verified_at' => now(),
        ], $overrides));

        return [$user, $employee];
    }
}
