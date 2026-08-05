<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeExtendedProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_fields_are_nullable_and_model_casts_sensitive_values(): void
    {
        [, $employee] = $this->employeeUser();

        $this->assertNull($employee->family_card_number);
        $this->assertNull($employee->domicile_same_as_identity);

        $employee->update([
            'family_card_number' => '3201010101010001',
            'domicile_same_as_identity' => true,
        ]);

        $rawValue = DB::table('employees')->where('id', $employee->id)->value('family_card_number');
        $this->assertNotSame('3201010101010001', $rawValue);
        $this->assertSame('3201010101010001', $employee->refresh()->family_card_number);
        $this->assertTrue($employee->domicile_same_as_identity);
    }

    public function test_profile_routes_enforce_authentication_role_and_current_employee_ownership(): void
    {
        $this->get('/pegawai/profile')->assertRedirect('/login');

        $panitia = User::factory()->create(['role' => 'panitia']);
        $this->actingAs($panitia)->get('/pegawai/profile')->assertForbidden();

        [$user, $employee] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser(email: 'other.profile@yapista.test');

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => 'Nama Pemilik',
                'whatsapp_number' => '081234567890',
                'employee_id' => $otherEmployee->id,
            ])
            ->assertRedirect(route('pegawai.profile.show', absolute: false));

        $this->assertSame('Nama Pemilik', $employee->refresh()->full_name);
        $this->assertNotSame('Nama Pemilik', $otherEmployee->refresh()->full_name);
    }

    public function test_only_draft_and_rejected_profiles_can_be_edited_without_changing_status(): void
    {
        foreach (['draft', 'rejected'] as $status) {
            [$user, $employee] = $this->employeeUser(
                ['verification_status' => $status],
                $status.'@yapista.test',
            );

            $this->actingAs($user)
                ->put('/pegawai/profile', ['full_name' => 'Profil '.$status, 'religion' => 'islam'])
                ->assertRedirect(route('pegawai.profile.show', absolute: false));

            $this->assertSame($status, $employee->refresh()->verification_status);
            $this->assertSame('islam', $employee->religion);
        }

        foreach (['submitted', 'verified'] as $status) {
            [$user, $employee] = $this->employeeUser(
                ['verification_status' => $status],
                $status.'@yapista.test',
            );

            $this->actingAs($user)
                ->get('/pegawai/profile/edit')
                ->assertRedirect(route('pegawai.profile.show', absolute: false));
            $this->actingAs($user)
                ->put('/pegawai/profile', ['full_name' => 'Tidak Berubah', 'religion' => 'islam'])
                ->assertRedirect(route('pegawai.profile.show', absolute: false));

            $this->assertNotSame('Tidak Berubah', $employee->refresh()->full_name);
            $this->assertSame($status, $employee->verification_status);
        }
    }

    public function test_employee_can_save_partial_draft_and_empty_optional_fields_become_null(): void
    {
        [$user, $employee] = $this->employeeUser([
            'phone' => '081234567890',
            'address' => 'Alamat lama',
        ]);

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'whatsapp_number' => '081298765432',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('081298765432', $employee->refresh()->whatsapp_number);
        $this->assertSame('Alamat lama', $employee->address);
        $this->assertSame('draft', $employee->verification_status);

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'nik' => '',
                'family_card_number' => '',
                'phone' => '',
                'whatsapp_number' => '',
                'email' => '',
                'identity_address' => '',
                'address' => '',
                'domicile_postal_code' => '',
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertNull($employee->nik);
        $this->assertNull($employee->family_card_number);
        $this->assertNull($employee->phone);
        $this->assertNull($employee->address);
    }

    public function test_identity_numbers_validate_format_uniqueness_and_allow_shared_family_card_number(): void
    {
        [$user, $employee] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser([
            'nik' => '3201010101010099',
            'family_card_number' => '3201010101010088',
        ], 'identity.other@yapista.test');

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'nik' => $otherEmployee->nik,
                'family_card_number' => '123',
            ])
            ->assertSessionHasErrors(['nik', 'family_card_number']);

        foreach (['123456789012345', '12345678901234567', '32010101ABC10001'] as $invalidNik) {
            $this->actingAs($user)
                ->put('/pegawai/profile', ['full_name' => $employee->full_name, 'nik' => $invalidNik])
                ->assertSessionHasErrors('nik');
        }

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'nik' => '3201010101010001',
                'family_card_number' => '3201010101010088',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('3201010101010088', $employee->refresh()->family_card_number);
    }

    public function test_contact_birth_blood_type_and_postal_code_validation(): void
    {
        [$user, $employee] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser(['email' => 'used.contact@yapista.test'], 'other.login@yapista.test');

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'email' => 'bukan-email',
                'birth_date' => now()->addDay()->format('Y-m-d'),
                'blood_type' => 'X',
                'domicile_postal_code' => '1234',
                'phone' => '12345',
            ])
            ->assertSessionHasErrors(['email', 'birth_date', 'blood_type', 'domicile_postal_code', 'phone']);

        $this->actingAs($user)
            ->put('/pegawai/profile', ['full_name' => $employee->full_name, 'email' => $otherEmployee->email])
            ->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'phone' => '+6281234567890',
                'whatsapp_number' => '081298765432',
                'birth_date' => '1990-01-01',
                'blood_type' => 'AB',
                'domicile_postal_code' => '17111',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_profile_update_protects_hr_only_and_legacy_fields(): void
    {
        [$user, $employee] = $this->employeeUser([
            'employee_number' => '7770924555',
            'verification_status' => 'draft',
        ]);
        [$otherInstitution, $otherPosition] = $this->institutionAndPosition('Manipulasi');

        $original = $employee->only([
            'employee_number', 'institution_id', 'position_id', 'employee_type',
            'employment_status', 'join_date', 'verification_status', 'user_id',
        ]);

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'employee_number' => '7770924999',
                'institution_id' => $otherInstitution->id,
                'position_id' => $otherPosition->id,
                'employee_type' => 'dosen',
                'employment_status' => 'resign',
                'join_date' => '2020-01-01',
                'verification_status' => 'verified',
                'verification_note' => 'manipulated',
                'verified_by' => $user->id,
                'verified_at' => now()->toDateTimeString(),
                'user_id' => null,
                'nup' => '7770924999',
                'foundation_registry_number' => 999,
            ])
            ->assertSessionHasNoErrors();

        $employee->refresh();
        foreach ($original as $field => $value) {
            $this->assertEquals($value, $employee->{$field});
        }
        $this->assertNull($employee->nup);
        $this->assertNull($employee->foundation_registry_number);
    }

    public function test_personal_email_does_not_change_login_email(): void
    {
        [$user, $employee] = $this->employeeUser(email: 'login.profile@yapista.test');

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'email' => 'personal.profile@yapista.test',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('personal.profile@yapista.test', $employee->refresh()->email);
        $this->assertSame('login.profile@yapista.test', $user->refresh()->email);
    }

    public function test_same_address_flag_copies_identity_address_without_erasing_old_domicile_when_false(): void
    {
        [$user, $employee] = $this->employeeUser(['address' => 'Domisili lama']);

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'identity_address' => 'Alamat sesuai KTP',
                'domicile_same_as_identity' => true,
                'address' => 'Nilai manipulasi browser',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Alamat sesuai KTP', $employee->refresh()->address);
        $this->assertTrue($employee->domicile_same_as_identity);

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'domicile_same_as_identity' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Alamat sesuai KTP', $employee->refresh()->address);
        $this->assertFalse($employee->domicile_same_as_identity);
    }

    public function test_profile_views_mask_family_card_and_keep_optional_inputs_optional(): void
    {
        [$user, $employee] = $this->employeeUser([
            'employee_number' => '7770924666',
            'family_card_number' => '3201010101011234',
        ]);

        $this->actingAs($user)
            ->get('/pegawai/profile')
            ->assertOk()
            ->assertSee('************1234')
            ->assertDontSee('3201010101011234')
            ->assertSee('7770924666')
            ->assertSee($employee->institution->name)
            ->assertSee($employee->position->name);

        $response = $this->actingAs($user)->get('/pegawai/profile/edit')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('value="3201010101011234"', $html);
        foreach (['nik', 'family_card_number', 'phone', 'whatsapp_number', 'email', 'identity_address', 'address'] as $field) {
            $this->assertDoesNotMatchRegularExpression('/name="'.preg_quote($field, '/').'"[^>]*\brequired\b/i', $html);
        }
        $this->assertStringContainsString('Simpan Draft', $html);
    }

    /**
     * @param  array<string, mixed>  $employeeOverrides
     * @return array{User, Employee}
     */
    private function employeeUser(array $employeeOverrides = [], string $email = 'extended.profile@yapista.test'): array
    {
        [$institution, $position] = $this->institutionAndPosition(uniqid());
        $user = User::factory()->create([
            'email' => $email,
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Profil',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $employeeOverrides));

        return [$user, $employee];
    }

    /** @return array{Institution, Position} */
    private function institutionAndPosition(string $suffix): array
    {
        $institution = Institution::create([
            'name' => 'Unit Profil '.$suffix,
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
