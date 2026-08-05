<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\EmployeeEducation;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeEducationCertificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_employee_may_have_empty_education_and_certification_collections(): void
    {
        [, $employee] = $this->employeeUser();

        $this->assertCount(0, $employee->educations);
        $this->assertCount(0, $employee->certifications);
        $this->assertNull($employee->highestEducation);
    }

    public function test_models_support_multiple_records_encryption_casts_and_cascade_delete(): void
    {
        [, $employee] = $this->employeeUser();
        $education = $this->education($employee, [
            'certificate_number' => 'IJAZAH-2020-1234',
            'start_year' => 2016,
            'graduation_year' => 2020,
            'is_highest' => true,
        ]);
        $this->education($employee, ['institution_name' => 'Universitas Kedua']);
        $certification = $this->certification($employee, [
            'certificate_number' => 'SERTIFIKAT-5678',
            'issued_at' => '2024-01-10',
            'expired_at' => '2027-01-10',
            'is_active' => true,
        ]);
        $this->certification($employee, ['name' => 'Sertifikasi Kedua']);

        $this->assertNotSame('IJAZAH-2020-1234', DB::table('employee_educations')->where('id', $education->id)->value('certificate_number'));
        $this->assertNotSame('SERTIFIKAT-5678', DB::table('employee_certifications')->where('id', $certification->id)->value('certificate_number'));
        $this->assertSame('IJAZAH-2020-1234', $education->refresh()->certificate_number);
        $this->assertSame('SERTIFIKAT-5678', $certification->refresh()->certificate_number);
        $this->assertTrue($education->is_highest);
        $this->assertTrue($certification->is_active);
        $this->assertSame('2024-01-10', $certification->issued_at->format('Y-m-d'));
        $this->assertCount(2, $employee->educations);
        $this->assertCount(2, $employee->certifications);

        $employee->delete();

        $this->assertDatabaseMissing('employee_educations', ['id' => $education->id]);
        $this->assertDatabaseMissing('employee_certifications', ['id' => $certification->id]);
    }

    public function test_draft_and_rejected_employees_can_create_minimal_records_without_changing_hr_data(): void
    {
        foreach (['draft', 'rejected'] as $index => $status) {
            [$user, $employee] = $this->employeeUser([
                'employee_number' => '7770931'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'verification_status' => $status,
            ], "education.{$status}@yapista.test");
            [, $otherEmployee] = $this->employeeUser([], "education.other.{$status}@yapista.test");
            $originalHrData = $employee->only(['employee_number', 'institution_id', 'position_id', 'employment_status', 'verification_status']);

            $this->actingAs($user)->post(route('pegawai.profile.educations.store', absolute: false), [
                'education_level' => 'sarjana',
                'institution_name' => 'Universitas Ibnu Sina',
                'employee_id' => $otherEmployee->id,
                'employee_number' => '7770931999',
                'verification_status' => 'verified',
            ])->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false))->assertSessionHasNoErrors();

            $this->actingAs($user)->post(route('pegawai.profile.certifications.store', absolute: false), [
                'name' => 'Sertifikat Pendidik',
                'employee_id' => $otherEmployee->id,
                'employment_status' => 'resign',
            ])->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false))->assertSessionHasNoErrors();

            $this->assertCount(1, $employee->educations);
            $this->assertCount(1, $employee->certifications);
            $this->assertCount(0, $otherEmployee->educations);
            $this->assertCount(0, $otherEmployee->certifications);
            $this->assertNull($employee->educations()->firstOrFail()->major);
            $this->assertNull($employee->certifications()->firstOrFail()->issuer);
            $this->assertSame($originalHrData, $employee->refresh()->only(array_keys($originalHrData)));
        }
    }

    public function test_education_validates_required_and_optional_fields(): void
    {
        [$user, $employee] = $this->employeeUser();
        $route = route('pegawai.profile.educations.store', absolute: false);
        $futureYear = (int) now()->format('Y') + 1;

        $this->actingAs($user)->post($route, [])->assertSessionHasErrors(['education_level', 'institution_name']);
        $this->actingAs($user)->post($route, [
            'education_level' => 'tidak_valid',
            'institution_name' => 'Sekolah Uji',
            'start_year' => $futureYear,
            'graduation_year' => $futureYear,
        ])->assertSessionHasErrors(['education_level', 'start_year', 'graduation_year']);
        $this->actingAs($user)->post($route, [
            'education_level' => 'magister',
            'institution_name' => 'Universitas Uji',
            'start_year' => 2020,
            'graduation_year' => 2019,
        ])->assertSessionHasErrors('graduation_year');

        $this->assertCount(0, $employee->educations);

        $this->actingAs($user)->post($route, [
            'education_level' => 'sarjana',
            'institution_name' => '  Universitas Minimal  ',
        ])->assertSessionHasNoErrors();

        $education = $employee->educations()->firstOrFail();
        $this->assertSame('Universitas Minimal', $education->institution_name);
        $this->assertNull($education->certificate_number);
        $this->assertFalse($education->is_highest);
    }

    public function test_employee_can_update_delete_and_keep_multiple_educations(): void
    {
        [$user, $employee] = $this->employeeUser();
        $education = $this->education($employee);
        $this->education($employee, ['education_level' => 'magister', 'institution_name' => 'Kampus Kedua']);

        $this->actingAs($user)->put(route('pegawai.profile.educations.update', $education, absolute: false), [
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Diperbarui',
            'major' => 'Manajemen Pendidikan',
        ])->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false))->assertSessionHasNoErrors();

        $this->assertSame('Universitas Diperbarui', $education->refresh()->institution_name);
        $this->assertSame('Manajemen Pendidikan', $education->major);
        $this->assertCount(2, $employee->educations);

        $this->actingAs($user)->delete(route('pegawai.profile.educations.destroy', $education, absolute: false))
            ->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false));

        $this->assertDatabaseMissing('employee_educations', ['id' => $education->id]);
    }

    public function test_only_one_highest_education_is_kept_per_employee(): void
    {
        [$user, $employee] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser([], 'highest.other@yapista.test');
        $otherHighest = $this->education($otherEmployee, ['is_highest' => true]);

        foreach ([['sarjana', 'Kampus Satu'], ['magister', 'Kampus Dua']] as [$level, $institution]) {
            $this->actingAs($user)->post(route('pegawai.profile.educations.store', absolute: false), [
                'education_level' => $level,
                'institution_name' => $institution,
                'is_highest' => true,
            ])->assertSessionHasNoErrors();
        }

        $first = $employee->educations()->where('institution_name', 'Kampus Satu')->firstOrFail();
        $second = $employee->educations()->where('institution_name', 'Kampus Dua')->firstOrFail();
        $this->assertFalse($first->is_highest);
        $this->assertTrue($second->is_highest);
        $this->assertTrue($otherHighest->refresh()->is_highest);
        $this->assertSame(1, $employee->educations()->where('is_highest', true)->count());

        $this->actingAs($user)->delete(route('pegawai.profile.educations.destroy', $second, absolute: false))->assertSessionHasNoErrors();
        $this->assertSame(0, $employee->educations()->where('is_highest', true)->count());
        $this->assertFalse($first->refresh()->is_highest);
    }

    public function test_certification_validates_fields_and_supports_crud(): void
    {
        [$user, $employee] = $this->employeeUser();
        $route = route('pegawai.profile.certifications.store', absolute: false);

        $this->actingAs($user)->post($route, [])->assertSessionHasErrors('name');
        $this->actingAs($user)->post($route, [
            'name' => 'Tanggal Tidak Valid',
            'issued_at' => now()->addDay()->format('Y-m-d'),
        ])->assertSessionHasErrors('issued_at');
        $this->actingAs($user)->post($route, [
            'name' => 'Rentang Tidak Valid',
            'issued_at' => '2024-06-10',
            'expired_at' => '2024-06-09',
        ])->assertSessionHasErrors('expired_at');

        $this->actingAs($user)->post($route, ['name' => '  Sertifikat Minimal  '])
            ->assertSessionHasNoErrors();

        $certification = $employee->certifications()->firstOrFail();
        $this->assertSame('Sertifikat Minimal', $certification->name);
        $this->assertNull($certification->certificate_number);
        $this->assertTrue($certification->is_active);

        $this->actingAs($user)->put(route('pegawai.profile.certifications.update', $certification, absolute: false), [
            'name' => 'Sertifikat Diperbarui',
            'issuer' => 'Lembaga Uji',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Lembaga Uji', $certification->refresh()->issuer);
        $this->assertTrue($certification->is_active);

        $this->actingAs($user)->delete(route('pegawai.profile.certifications.destroy', $certification, absolute: false))
            ->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false));
        $this->assertDatabaseMissing('employee_certifications', ['id' => $certification->id]);
    }

    public function test_certification_effective_statuses_are_computed_without_persistence(): void
    {
        [, $employee] = $this->employeeUser();

        $active = $this->certification($employee, ['expired_at' => now()->addYear()->format('Y-m-d')]);
        $expired = $this->certification($employee, ['name' => 'Expired', 'expired_at' => now()->subDay()->format('Y-m-d')]);
        $inactive = $this->certification($employee, ['name' => 'Inactive', 'is_active' => false]);
        $noExpiry = $this->certification($employee, ['name' => 'No Expiry', 'expired_at' => null]);

        $this->assertSame('active', $active->effective_status);
        $this->assertSame('expired', $expired->effective_status);
        $this->assertSame('inactive', $inactive->effective_status);
        $this->assertSame('no_expiry', $noExpiry->effective_status);
        $this->assertSame('Tidak Ada Masa Berlaku', $noExpiry->effective_status_label);
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('employee_certifications', 'effective_status'));
    }

    public function test_routes_reject_guest_panitia_and_cross_employee_access(): void
    {
        [, $ownerEmployee] = $this->employeeUser([], 'records.owner@yapista.test');
        $education = $this->education($ownerEmployee);
        $certification = $this->certification($ownerEmployee);
        [$attacker] = $this->employeeUser([], 'records.attacker@yapista.test');
        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);

        $this->get(route('pegawai.profile.educations.create', absolute: false))->assertRedirect('/login');
        $this->get(route('pegawai.profile.certifications.create', absolute: false))->assertRedirect('/login');
        $this->actingAs($panitia)->get(route('pegawai.profile.educations.create', absolute: false))->assertForbidden();
        $this->actingAs($panitia)->get(route('pegawai.profile.certifications.create', absolute: false))->assertForbidden();

        $this->actingAs($attacker)->get(route('pegawai.profile.educations.edit', $education, absolute: false))->assertNotFound();
        $this->actingAs($attacker)->put(route('pegawai.profile.educations.update', $education, absolute: false), $this->educationPayload())->assertNotFound();
        $this->actingAs($attacker)->delete(route('pegawai.profile.educations.destroy', $education, absolute: false))->assertNotFound();
        $this->actingAs($attacker)->get(route('pegawai.profile.certifications.edit', $certification, absolute: false))->assertNotFound();
        $this->actingAs($attacker)->put(route('pegawai.profile.certifications.update', $certification, absolute: false), ['name' => 'Manipulasi'])->assertNotFound();
        $this->actingAs($attacker)->delete(route('pegawai.profile.certifications.destroy', $certification, absolute: false))->assertNotFound();

        $this->assertSame('Universitas Uji', $education->refresh()->institution_name);
        $this->assertSame('Sertifikat Uji', $certification->refresh()->name);
    }

    public function test_submitted_and_verified_profiles_cannot_crud_education_or_certification(): void
    {
        foreach (['submitted', 'verified'] as $status) {
            [$user, $employee] = $this->employeeUser(['verification_status' => $status], "locked.records.{$status}@yapista.test");
            $education = $this->education($employee);
            $certification = $this->certification($employee);
            $profile = route('pegawai.profile.show', absolute: false);

            $this->actingAs($user)->get(route('pegawai.profile.educations.create', absolute: false))->assertRedirect($profile);
            $this->actingAs($user)->post(route('pegawai.profile.educations.store', absolute: false), $this->educationPayload())->assertRedirect($profile);
            $this->actingAs($user)->get(route('pegawai.profile.educations.edit', $education, absolute: false))->assertRedirect($profile);
            $this->actingAs($user)->put(route('pegawai.profile.educations.update', $education, absolute: false), $this->educationPayload(['institution_name' => 'Tidak Diubah']))->assertRedirect($profile);
            $this->actingAs($user)->delete(route('pegawai.profile.educations.destroy', $education, absolute: false))->assertRedirect($profile);
            $this->actingAs($user)->get(route('pegawai.profile.certifications.create', absolute: false))->assertRedirect($profile);
            $this->actingAs($user)->post(route('pegawai.profile.certifications.store', absolute: false), ['name' => 'Tidak Dibuat'])->assertRedirect($profile);
            $this->actingAs($user)->get(route('pegawai.profile.certifications.edit', $certification, absolute: false))->assertRedirect($profile);
            $this->actingAs($user)->put(route('pegawai.profile.certifications.update', $certification, absolute: false), ['name' => 'Tidak Diubah'])->assertRedirect($profile);
            $this->actingAs($user)->delete(route('pegawai.profile.certifications.destroy', $certification, absolute: false))->assertRedirect($profile);

            $this->assertCount(1, $employee->educations);
            $this->assertCount(1, $employee->certifications);
            $this->assertSame('Universitas Uji', $education->refresh()->institution_name);
            $this->assertSame('Sertifikat Uji', $certification->refresh()->name);
            $this->assertSame($status, $employee->refresh()->verification_status);
        }
    }

    public function test_profile_views_show_empty_states_mask_values_and_hide_locked_actions(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->get('/pegawai/profile')
            ->assertOk()
            ->assertSee('Belum ada riwayat pendidikan yang ditambahkan')
            ->assertSee('Belum ada sertifikasi atau kompetensi yang ditambahkan')
            ->assertSee('Tambah Pendidikan')
            ->assertSee('Tambah Sertifikasi');

        $education = $this->education($employee, ['certificate_number' => 'IJAZAH-RAHASIA-1234']);
        $certification = $this->certification($employee, ['certificate_number' => 'SERTIFIKAT-RAHASIA-5678']);
        $show = $this->actingAs($user)->get('/pegawai/profile')->assertOk();
        $show->assertSee($education->masked_certificate_number)
            ->assertSee($certification->masked_certificate_number)
            ->assertDontSee('IJAZAH-RAHASIA-1234')
            ->assertDontSee('SERTIFIKAT-RAHASIA-5678')
            ->assertSee('table-responsive');

        $educationEdit = $this->actingAs($user)->get(route('pegawai.profile.educations.edit', $education, absolute: false))->assertOk();
        $educationEdit->assertSee('value="IJAZAH-RAHASIA-1234"', false)->assertDontSee('type="file"', false);
        $certificationEdit = $this->actingAs($user)->get(route('pegawai.profile.certifications.edit', $certification, absolute: false))->assertOk();
        $certificationEdit->assertSee('value="SERTIFIKAT-RAHASIA-5678"', false)->assertDontSee('type="file"', false);

        $employee->update(['verification_status' => 'verified']);
        $user->unsetRelation('employee');
        $this->actingAs($user)->get('/pegawai/profile')
            ->assertOk()
            ->assertDontSee('Tambah Pendidikan')
            ->assertDontSee('Tambah Sertifikasi')
            ->assertDontSee('Hapus data pendidikan ini?')
            ->assertDontSee('Hapus data sertifikasi ini?');
    }

    /**
     * @param  array<string, mixed>  $employeeOverrides
     * @return array{User, Employee}
     */
    private function employeeUser(array $employeeOverrides = [], string $email = 'education.profile@yapista.test'): array
    {
        $institution = Institution::create(['name' => 'Unit '.uniqid(), 'level' => 'SMK', 'status' => 'active']);
        $position = Position::create(['institution_id' => $institution->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);
        $user = User::factory()->create(['email' => $email, 'role' => 'pegawai', 'status' => 'active']);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Pendidikan',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $employeeOverrides));

        return [$user, $employee];
    }

    /** @param array<string, mixed> $overrides */
    private function education(Employee $employee, array $overrides = []): EmployeeEducation
    {
        return $employee->educations()->create(array_merge($this->educationPayload(), $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function certification(Employee $employee, array $overrides = []): EmployeeCertification
    {
        return $employee->certifications()->create(array_merge([
            'name' => 'Sertifikat Uji',
            'is_active' => true,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function educationPayload(array $overrides = []): array
    {
        return array_merge([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Uji',
        ], $overrides);
    }
}
