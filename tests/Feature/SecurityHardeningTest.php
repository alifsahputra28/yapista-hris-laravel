<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Models\EmployeeFamilyMember;
use App\Models\EmployeeInvitation;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Unit Security Test',
            'level' => 'Yayasan',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Staf Security Test',
            'type' => 'struktural',
            'status' => 'active',
        ]);
    }

    public function test_invitation_registration_is_bound_to_the_invited_email(): void
    {
        $employee = $this->employee(null, ['email' => 'invited@yapista.test']);
        $invitation = $this->invitation($employee);

        $this->post(route('invitation.register.store', $invitation->invitation_code, absolute: false), [
            'name' => 'Pemakai Undangan',
            'email' => 'attacker@yapista.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'attacker@yapista.test']);
        $this->assertSame('unused', $invitation->refresh()->status);
    }

    public function test_new_invitation_uses_a_high_entropy_code_and_requires_employee_email(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('employees.invitations.generate', $employee, absolute: false))
            ->assertRedirect(route('invitations.index', absolute: false));

        $invitation = $employee->invitations()->firstOrFail();
        $this->assertMatchesRegularExpression('/\AYAPISTA-REG-[A-Z0-9]{32}\z/', $invitation->invitation_code);

        $employeeWithoutEmail = $this->employee(null, ['email' => null]);

        $this->actingAs($admin)
            ->post(route('employees.invitations.generate', $employeeWithoutEmail, absolute: false))
            ->assertRedirect(route('employees.index', absolute: false))
            ->assertSessionHas('error');

        $this->assertFalse($employeeWithoutEmail->invitations()->exists());
    }

    public function test_employee_photo_is_private_and_access_is_limited_to_owner_and_hr(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $owner = User::factory()->create(['role' => 'pegawai']);
        $employee = $this->employee($owner, ['photo' => 'employee-photos/owner.jpg']);
        UploadedFile::fake()->image('owner.jpg')->storeAs('employee-photos', 'owner.jpg', 'private');

        $otherUser = User::factory()->create(['role' => 'pegawai']);
        $this->employee($otherUser);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $admin = User::factory()->create(['role' => 'hr_admin']);

        $this->get(route('employees.photo', $employee, absolute: false))
            ->assertRedirect(route('login', absolute: false));

        $ownerResponse = $this->actingAs($owner)
            ->get(route('employees.photo', $employee, absolute: false))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->assertStringContainsString('private', (string) $ownerResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $ownerResponse->headers->get('Cache-Control'));

        $this->actingAs($otherUser)
            ->get(route('employees.photo', $employee, absolute: false))
            ->assertNotFound();

        $this->actingAs($panitia)
            ->get(route('employees.photo', $employee, absolute: false))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('employees.photo', $employee, absolute: false))
            ->assertOk();
    }

    public function test_photo_path_traversal_is_rejected(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'pegawai']);
        $employee = $this->employee($user, ['photo' => '../.env']);

        $this->actingAs($user)
            ->get(route('employees.photo', $employee, absolute: false))
            ->assertNotFound();
    }

    public function test_legacy_photo_migration_is_dry_run_by_default_and_idempotent_on_commit(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $path = 'employees/photos/legacy.jpg';
        $this->employee(null, ['photo' => $path]);
        UploadedFile::fake()->image('legacy.jpg')->storeAs('employees/photos', 'legacy.jpg', 'public');

        $this->artisan('employee-security:migrate-photos-private')
            ->expectsOutput('Mode: dry-run')
            ->expectsOutput('Ready: 1')
            ->assertSuccessful();

        Storage::disk('public')->assertExists($path);
        Storage::disk('private')->assertMissing($path);

        $this->artisan('employee-security:migrate-photos-private', ['--commit' => true])
            ->expectsOutput('Migrated: 1')
            ->assertSuccessful();

        Storage::disk('private')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        $this->artisan('employee-security:migrate-photos-private', ['--commit' => true])
            ->expectsOutput('Already private: 1')
            ->expectsOutput('Migrated: 0')
            ->assertSuccessful();
    }

    public function test_photo_upload_rejects_unsupported_and_oversized_files(): void
    {
        Storage::fake('private');
        $admin = User::factory()->create(['role' => 'super_admin']);
        $payload = $this->validEmployeePayload();

        $this->actingAs($admin)
            ->post(route('employees.store', absolute: false), $payload + [
                'photo' => UploadedFile::fake()->create('profile.svg', 10, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('photo');

        $this->actingAs($admin)
            ->post(route('employees.store', absolute: false), $payload + [
                'email' => 'large-photo@yapista.test',
                'photo' => UploadedFile::fake()->image('large.jpg')->size(2049),
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_sensitive_encrypted_fields_and_internal_paths_are_hidden_from_serialization(): void
    {
        $employee = $this->employee(null, ['family_card_number' => '3201010101010001']);
        $family = $employee->familyMembers()->create([
            'full_name' => 'Keluarga Test',
            'relationship' => 'child',
            'nik' => '3201010101010002',
        ]);
        $education = $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Test',
            'certificate_number' => 'IJAZAH-SECRET',
        ]);
        $certification = $employee->certifications()->create([
            'name' => 'Sertifikasi Test',
            'certificate_number' => 'CERT-SECRET',
            'is_active' => true,
        ]);
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employee-documents/private-name.pdf',
            'original_name' => 'dokumen.pdf',
            'status' => 'pending',
        ]);
        $invitation = $this->invitation($employee);

        $this->assertArrayNotHasKey('family_card_number', $employee->toArray());
        $this->assertArrayNotHasKey('nik', $family->toArray());
        $this->assertArrayNotHasKey('certificate_number', $education->toArray());
        $this->assertArrayNotHasKey('certificate_number', $certification->toArray());
        $this->assertArrayNotHasKey('file_path', $document->toArray());
        $this->assertArrayNotHasKey('invitation_code', $invitation->toArray());
    }

    public function test_security_headers_are_added_without_a_content_security_policy_guess(): void
    {
        $this->get(route('login', absolute: false))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeaderMissing('Content-Security-Policy');
    }

    private function invitation(Employee $employee): EmployeeInvitation
    {
        return EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'invitation_code' => 'YAPISTA-REG-'.str_repeat('A', 32),
            'email' => $employee->email,
            'status' => 'unused',
            'expired_at' => now()->addDay(),
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function employee(?User $user = null, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'user_id' => $user?->id,
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'full_name' => 'Pegawai Security Test',
            'email' => $user?->email ?? 'employee-'.uniqid().'@yapista.test',
            'employee_type' => 'staff_yayasan',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function validEmployeePayload(): array
    {
        return [
            'full_name' => 'Pegawai Upload Test',
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'email' => 'upload-test@yapista.test',
            'employee_type' => 'staff_yayasan',
            'employment_status' => 'aktif',
        ];
    }
}
