<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeProfileWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_wizard_routes_are_limited_to_authenticated_employees_and_invalid_steps_are_not_found(): void
    {
        $route = route('pegawai.profile.wizard.show', 'identification', absolute: false);

        $this->get($route)->assertRedirect(route('login', absolute: false));

        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        $this->actingAs($panitia)->get($route)->assertForbidden();

        [$user] = $this->employeeUser();
        $this->actingAs($user)
            ->get(route('pegawai.profile.wizard.show', 'unknown-step', absolute: false))
            ->assertNotFound();
    }

    public function test_each_wizard_step_renders_and_direct_navigation_is_allowed(): void
    {
        [$user] = $this->employeeUser();
        $labels = [
            'identification' => 'Identitas Pribadi',
            'contact-address' => 'Kontak & Alamat',
            'family' => 'Keluarga',
            'education' => 'Pendidikan',
            'administration' => 'Bank & BPJS',
            'review' => 'Dokumen & Kirim',
        ];

        foreach ($labels as $step => $label) {
            $this->actingAs($user)
                ->get(route('pegawai.profile.wizard.show', $step, absolute: false))
                ->assertOk()
                ->assertSee($label);
        }
    }

    public function test_wizard_index_opens_first_incomplete_step_and_complete_profile_opens_review(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)
            ->get(route('pegawai.profile.wizard.index', absolute: false))
            ->assertRedirect(route('pegawai.profile.wizard.show', 'identification', absolute: false));

        $this->completeProfile($employee);
        $user->unsetRelation('employee');

        $this->actingAs($user)
            ->get(route('pegawai.profile.wizard.index', absolute: false))
            ->assertRedirect(route('pegawai.profile.wizard.show', 'review', absolute: false));
    }

    public function test_identification_step_saves_draft_navigates_and_ignores_hr_fields(): void
    {
        [$user, $employee] = $this->employeeUser([
            'employee_number' => '7770923991',
            'verification_status' => 'rejected',
        ]);
        $originalInstitution = $employee->institution_id;

        $payload = [
            'full_name' => 'Nama Profil Baru',
            'nik' => '3201010101010001',
            'family_card_number' => '3201010101010002',
            'birth_place' => 'Bandung',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'religion' => 'islam',
            'marital_status' => 'single',
            'nationality' => 'Indonesia',
            'blood_type' => 'O',
            'employee_number' => '1111111111',
            'institution_id' => 999,
            'verification_status' => 'verified',
            'wizard_action' => 'stay',
        ];

        $this->actingAs($user)
            ->put(route('pegawai.profile.wizard.identification.update', absolute: false), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pegawai.profile.wizard.show', 'identification', absolute: false));

        $employee->refresh();
        $this->assertSame('Nama Profil Baru', $employee->full_name);
        $this->assertSame('7770923991', $employee->employee_number);
        $this->assertSame($originalInstitution, $employee->institution_id);
        $this->assertSame('rejected', $employee->verification_status);

        $payload['wizard_action'] = 'next';
        $this->actingAs($user)
            ->patch(route('pegawai.profile.wizard.identification.update', absolute: false), $payload)
            ->assertRedirect(route('pegawai.profile.wizard.show', 'contact-address', absolute: false));
    }

    public function test_identification_step_validates_sensitive_identity_fields(): void
    {
        [$user] = $this->employeeUser();
        $route = route('pegawai.profile.wizard.identification.update', absolute: false);

        $this->actingAs($user)->from($route)->put($route, [
            'full_name' => 'Pegawai Validasi',
            'nik' => '12345',
            'family_card_number' => '12345',
            'birth_date' => now()->addDay()->format('Y-m-d'),
            'gender' => 'unknown',
            'wizard_action' => 'stay',
        ])->assertSessionHasErrors(['nik', 'family_card_number', 'birth_date', 'gender']);
    }

    public function test_contact_step_saves_same_address_without_changing_account_email(): void
    {
        [$user, $employee] = $this->employeeUser([], 'wizard.contact@yapista.test');
        $payload = [
            'phone' => '081234567890',
            'whatsapp_number' => '081234567891',
            'email' => 'personal.contact@yapista.test',
            'identity_address' => 'Jalan Identitas 1',
            'domicile_same_as_identity' => '1',
            'address' => 'Alamat yang harus ditimpa',
            'domicile_province' => 'Jawa Barat',
            'domicile_city' => 'Bandung',
            'domicile_district' => 'Coblong',
            'domicile_village' => 'Dago',
            'domicile_postal_code' => '40135',
            'wizard_action' => 'next',
        ];

        $this->actingAs($user)
            ->put(route('pegawai.profile.wizard.contact-address.update', absolute: false), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pegawai.profile.wizard.show', 'family', absolute: false));

        $employee->refresh();
        $this->assertSame('personal.contact@yapista.test', $employee->email);
        $this->assertSame('Jalan Identitas 1', $employee->address);
        $this->assertSame('wizard.contact@yapista.test', $user->fresh()->email);
    }

    public function test_contact_step_validates_phone_email_and_postal_code(): void
    {
        [$user] = $this->employeeUser();
        $route = route('pegawai.profile.wizard.contact-address.update', absolute: false);

        $this->actingAs($user)->put($route, [
            'phone' => 'nomor-salah',
            'whatsapp_number' => '123',
            'email' => 'bukan-email',
            'domicile_postal_code' => '1234',
            'wizard_action' => 'stay',
        ])->assertSessionHasErrors(['phone', 'whatsapp_number', 'email', 'domicile_postal_code']);
    }

    public function test_family_and_administration_steps_use_existing_crud_and_wizard_redirects(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)
            ->put(route('pegawai.profile.wizard.family.update', absolute: false), [
                'emergency_contact_name' => 'Kontak Darurat',
                'emergency_contact_relationship' => 'Saudara',
                'emergency_contact_phone' => '081234567892',
                'wizard_action' => 'next',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false));

        $this->actingAs($user)
            ->post(route('pegawai.profile.family-members.store', absolute: false), [
                'full_name' => 'Anggota Keluarga',
                'relationship' => 'child',
            ])
            ->assertRedirect(route('pegawai.profile.wizard.show', 'family', absolute: false));

        $this->actingAs($user)
            ->post(route('pegawai.profile.educations.store', absolute: false), [
                'education_level' => 'sarjana',
                'institution_name' => 'Universitas Uji',
                'is_highest' => '1',
            ])
            ->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false));

        $this->actingAs($user)
            ->post(route('pegawai.profile.certifications.store', absolute: false), ['name' => 'Sertifikasi Uji'])
            ->assertRedirect(route('pegawai.profile.wizard.show', 'education', absolute: false));

        $this->actingAs($user)
            ->put(route('pegawai.profile.administrative-details.update', absolute: false), [
                'bank_name' => 'Bank Uji',
                'wizard_action' => 'next',
            ])
            ->assertRedirect(route('pegawai.profile.wizard.show', 'review', absolute: false));

        $this->assertSame('Kontak Darurat', $employee->fresh()->emergency_contact_name);
        $this->assertCount(1, $employee->familyMembers);
        $this->assertCount(1, $employee->educations);
        $this->assertCount(1, $employee->certifications);
        $this->assertSame('Bank Uji', $employee->administrativeDetail->bank_name);
    }

    public function test_submitted_and_verified_profiles_are_read_only_and_updates_are_blocked(): void
    {
        foreach (['submitted', 'verified'] as $index => $status) {
            [$user, $employee] = $this->employeeUser([
                'verification_status' => $status,
                'employee_number' => $status === 'verified' ? '77709239'.str_pad((string) $index, 2, '0', STR_PAD_LEFT) : null,
            ], "wizard.locked.{$status}@yapista.test");

            $this->actingAs($user)
                ->get(route('pegawai.profile.wizard.show', 'identification', absolute: false))
                ->assertOk()
                ->assertSee('tidak dapat diubah')
                ->assertDontSee('Simpan Draft');

            $this->actingAs($user)
                ->put(route('pegawai.profile.wizard.identification.update', absolute: false), [
                    'full_name' => 'Nama Tidak Boleh Berubah',
                    'wizard_action' => 'stay',
                ])
                ->assertRedirect(route('pegawai.profile.wizard.show', 'identification', absolute: false))
                ->assertSessionHas('error');

            $this->assertNotSame('Nama Tidak Boleh Berubah', $employee->fresh()->full_name);
        }
    }

    public function test_review_masks_sensitive_values_and_shows_incomplete_submission_state(): void
    {
        [$user, $employee] = $this->employeeUser([
            'nik' => '3201010101011234',
            'family_card_number' => '3201010101015678',
        ]);
        $employee->familyMembers()->create([
            'full_name' => 'Pasangan Uji',
            'relationship' => 'spouse',
            'nik' => '3201010101019012',
        ]);
        $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Uji',
            'certificate_number' => 'IJAZAH-RAHASIA-1234',
            'is_highest' => true,
        ]);
        $employee->certifications()->create([
            'name' => 'Sertifikasi Uji',
            'certificate_number' => 'SERTIFIKAT-RAHASIA-5678',
        ]);
        $employee->administrativeDetail()->create([
            'bank_name' => 'Bank Uji',
            'bank_account_number' => '001122334455',
            'tax_status' => 'registered',
            'tax_identification_number' => '0011223344556677',
            'bpjs_health_status' => 'active',
            'bpjs_health_number' => '001122334466',
            'bpjs_employment_status' => 'active',
            'bpjs_employment_number' => '001122334477',
        ]);

        $response = $this->actingAs($user)
            ->get(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertOk()
            ->assertSee('Dokumen & Kirim')
            ->assertSee('************1234')
            ->assertSee('************5678')
            ->assertSee('Kirim untuk Verifikasi')
            ->assertSee('Profil belum dapat dikirim')
            ->assertDontSee('Submit Final');

        foreach (['3201010101011234', '3201010101015678', '3201010101019012', 'IJAZAH-RAHASIA-1234', 'SERTIFIKAT-RAHASIA-5678', '001122334455', '0011223344556677', '001122334466', '001122334477'] as $secret) {
            $response->assertDontSee($secret);
        }
    }

    public function test_profile_show_exposes_dynamic_progress_and_legacy_edit_route_still_works(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)
            ->get(route('pegawai.profile.show', absolute: false))
            ->assertOk()
            ->assertSee('Kelengkapan Profil')
            ->assertSee('Perbarui Data');

        $this->actingAs($user)
            ->get(route('pegawai.profile.edit', absolute: false))
            ->assertOk();
    }

    /** @param array<string, mixed> $employeeOverrides */
    private function employeeUser(array $employeeOverrides = [], string $email = 'wizard.profile@yapista.test'): array
    {
        $institution = Institution::create(['name' => 'Unit '.uniqid(), 'level' => 'SMK', 'status' => 'active']);
        $position = Position::create(['institution_id' => $institution->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);
        $user = User::factory()->create(['email' => $email, 'role' => 'pegawai', 'status' => 'active']);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Wizard',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $employeeOverrides));

        return [$user, $employee];
    }

    private function completeProfile(Employee $employee): void
    {
        $employee->update([
            'full_name' => 'Pegawai Lengkap',
            'nik' => '3201010101012222',
            'family_card_number' => '3201010101013333',
            'birth_place' => 'Bandung',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'religion' => 'islam',
            'marital_status' => 'single',
            'nationality' => 'Indonesia',
            'photo' => 'employees/photos/demo.jpg',
            'phone' => '081234567890',
            'whatsapp_number' => '081234567891',
            'email' => 'complete.profile@yapista.test',
            'identity_address' => 'Jalan Identitas',
            'address' => 'Jalan Domisili',
            'domicile_province' => 'Jawa Barat',
            'domicile_city' => 'Bandung',
            'domicile_district' => 'Coblong',
            'domicile_village' => 'Dago',
            'domicile_postal_code' => '40135',
            'emergency_contact_name' => 'Kontak Darurat',
            'emergency_contact_relationship' => 'Saudara',
            'emergency_contact_phone' => '081234567892',
        ]);
        $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Uji',
            'is_highest' => true,
        ]);
        $employee->administrativeDetail()->create([
            'bank_name' => 'Bank Uji',
            'bank_account_number' => '001122334455',
            'bank_account_holder' => 'Pegawai Lengkap',
            'tax_status' => 'not_registered',
            'bpjs_health_status' => 'not_registered',
            'bpjs_employment_status' => 'not_registered',
        ]);
    }
}
