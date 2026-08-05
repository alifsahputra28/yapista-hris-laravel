<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeFamilyMember;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeFamilyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_employee_can_have_no_emergency_contact_or_family_members(): void
    {
        [, $employee] = $this->employeeUser();

        $this->assertNull($employee->emergency_contact_name);
        $this->assertNull($employee->emergency_contact_relationship);
        $this->assertNull($employee->emergency_contact_phone);
        $this->assertNull($employee->emergency_contact_address);
        $this->assertCount(0, $employee->familyMembers);
    }

    public function test_family_member_relation_encrypts_nik_casts_values_and_cascades_on_employee_delete(): void
    {
        [, $employee] = $this->employeeUser();
        $familyMember = $employee->familyMembers()->create([
            'full_name' => 'Anak Pegawai',
            'relationship' => 'child',
            'nik' => '3201010101010001',
            'birth_date' => '2015-05-10',
            'is_dependent' => true,
        ]);

        $rawNik = DB::table('employee_family_members')->where('id', $familyMember->id)->value('nik');
        $this->assertNotSame('3201010101010001', $rawNik);
        $this->assertSame('3201010101010001', $familyMember->refresh()->nik);
        $this->assertSame('2015-05-10', $familyMember->birth_date->format('Y-m-d'));
        $this->assertTrue($familyMember->is_dependent);
        $this->assertSame('Anak', $familyMember->relationship_label);

        $employee->delete();
        $this->assertDatabaseMissing('employee_family_members', ['id' => $familyMember->id]);
    }

    public function test_emergency_contact_is_optional_and_partial_updates_do_not_change_status(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)
            ->put('/pegawai/profile', ['full_name' => $employee->full_name])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'emergency_contact_name' => 'Ibu Pegawai',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Ibu Pegawai', $employee->refresh()->emergency_contact_name);
        $this->assertNull($employee->emergency_contact_phone);
        $this->assertSame('draft', $employee->verification_status);

        foreach (['+6281234567890', '081298765432'] as $phone) {
            $this->actingAs($user)
                ->put('/pegawai/profile', [
                    'full_name' => $employee->full_name,
                    'emergency_contact_phone' => $phone,
                ])
                ->assertSessionHasNoErrors();
            $this->assertSame($phone, $employee->refresh()->emergency_contact_phone);
        }

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => $employee->full_name,
                'emergency_contact_phone' => 'telepon-invalid',
            ])
            ->assertSessionHasErrors('emergency_contact_phone');
    }

    public function test_draft_and_rejected_employees_can_create_minimal_family_member_without_changing_hr_fields(): void
    {
        foreach (['draft', 'rejected'] as $index => $status) {
            [$user, $employee] = $this->employeeUser([
                'employee_number' => '7770925'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'verification_status' => $status,
            ], $status.'.family@yapista.test');
            [, $otherEmployee] = $this->employeeUser([], $status.'.other@yapista.test');

            $this->actingAs($user)
                ->post(route('pegawai.profile.family-members.store', absolute: false), [
                    'full_name' => 'Keluarga '.$status,
                    'relationship' => 'sibling',
                    'employee_id' => $otherEmployee->id,
                    'employee_number' => '7770925999',
                    'verification_status' => 'verified',
                ])
                ->assertRedirect(route('pegawai.profile.wizard.show', 'family', absolute: false));

            $member = $employee->familyMembers()->firstOrFail();
            $this->assertSame($employee->id, $member->employee_id);
            $this->assertSame('Keluarga '.$status, $member->full_name);
            $this->assertNull($member->nik);
            $this->assertFalse($member->is_dependent);
            $this->assertSame($status, $employee->refresh()->verification_status);
            $this->assertNotSame('7770925999', $employee->employee_number);
            $this->assertCount(0, $otherEmployee->familyMembers);
        }
    }

    public function test_family_member_requires_minimum_identity_and_validates_optional_fields(): void
    {
        [$user, $employee] = $this->employeeUser();
        $route = route('pegawai.profile.family-members.store', absolute: false);

        $this->actingAs($user)->post($route, [])->assertSessionHasErrors(['full_name', 'relationship']);
        $this->actingAs($user)->post($route, [
            'full_name' => 'Keluarga Invalid',
            'relationship' => 'unknown',
            'nik' => '123',
            'birth_date' => now()->addDay()->format('Y-m-d'),
            'bpjs_status' => 'unknown',
        ])->assertSessionHasErrors(['relationship', 'nik', 'birth_date', 'bpjs_status']);

        $this->assertCount(0, $employee->familyMembers);

        $this->actingAs($user)->post($route, [
            'full_name' => 'Keluarga Valid',
            'relationship' => 'guardian',
            'nik' => '3201010101010002',
            'birth_place' => 'Bekasi',
            'birth_date' => '1970-01-01',
            'gender' => 'female',
            'occupation' => 'Wiraswasta',
            'is_dependent' => true,
            'bpjs_status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('employee_family_members', 1);
    }

    public function test_family_nik_is_not_unique_and_employee_can_have_multiple_members(): void
    {
        [$firstUser, $firstEmployee] = $this->employeeUser();
        [$secondUser, $secondEmployee] = $this->employeeUser([], 'shared.family@yapista.test');
        $nik = '3201010101010010';

        foreach ([[$firstUser, 'Anak Pertama'], [$firstUser, 'Anak Kedua'], [$secondUser, 'Saudara Bersama']] as [$user, $name]) {
            $this->actingAs($user)
                ->post(route('pegawai.profile.family-members.store', absolute: false), [
                    'full_name' => $name,
                    'relationship' => 'child',
                    'nik' => $nik,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertCount(2, $firstEmployee->familyMembers);
        $this->assertCount(1, $secondEmployee->familyMembers);
        $this->assertSame($nik, $secondEmployee->familyMembers()->firstOrFail()->nik);
    }

    public function test_employee_can_update_and_delete_own_family_member(): void
    {
        [$user, $employee] = $this->employeeUser();
        $member = $this->familyMember($employee);

        $this->actingAs($user)
            ->put(route('pegawai.profile.family-members.update', $member, absolute: false), [
                'full_name' => 'Nama Diperbarui',
                'relationship' => 'father',
                'is_dependent' => false,
            ])
            ->assertRedirect(route('pegawai.profile.wizard.show', 'family', absolute: false));

        $this->assertSame('Nama Diperbarui', $member->refresh()->full_name);
        $this->assertSame('father', $member->relationship);
        $this->assertSame('draft', $employee->refresh()->verification_status);

        $this->actingAs($user)
            ->delete(route('pegawai.profile.family-members.destroy', $member, absolute: false))
            ->assertRedirect(route('pegawai.profile.wizard.show', 'family', absolute: false));

        $this->assertDatabaseMissing('employee_family_members', ['id' => $member->id]);
    }

    public function test_family_routes_reject_guest_panitia_and_other_employee_records(): void
    {
        [$owner, $ownerEmployee] = $this->employeeUser();
        $member = $this->familyMember($ownerEmployee);
        [$otherUser] = $this->employeeUser([], 'family.attacker@yapista.test');
        $panitia = User::factory()->create(['role' => 'panitia']);

        $this->get(route('pegawai.profile.family-members.create', absolute: false))->assertRedirect('/login');
        $this->post(route('pegawai.profile.family-members.store', absolute: false), [
            'full_name' => 'Guest', 'relationship' => 'other',
        ])->assertRedirect('/login');
        $this->actingAs($panitia)->get(route('pegawai.profile.family-members.create', absolute: false))->assertForbidden();

        $this->actingAs($otherUser)->get(route('pegawai.profile.family-members.edit', $member, absolute: false))->assertNotFound();
        $this->actingAs($otherUser)->put(route('pegawai.profile.family-members.update', $member, absolute: false), [
            'full_name' => 'Manipulasi', 'relationship' => 'other',
        ])->assertNotFound();
        $this->actingAs($otherUser)->delete(route('pegawai.profile.family-members.destroy', $member, absolute: false))->assertNotFound();

        $this->assertSame('Anggota Keluarga', $member->refresh()->full_name);
        $this->assertSame($owner->id, $ownerEmployee->user_id);
    }

    public function test_submitted_and_verified_profiles_can_not_crud_family_members(): void
    {
        foreach (['submitted', 'verified'] as $status) {
            [$user, $employee] = $this->employeeUser(
                ['verification_status' => $status],
                'locked.'.$status.'@yapista.test',
            );
            $member = $this->familyMember($employee);

            $this->actingAs($user)->get(route('pegawai.profile.family-members.create', absolute: false))->assertRedirect(route('pegawai.profile.show', absolute: false));
            $this->actingAs($user)->post(route('pegawai.profile.family-members.store', absolute: false), [
                'full_name' => 'Tidak Dibuat', 'relationship' => 'other',
            ])->assertRedirect(route('pegawai.profile.show', absolute: false));
            $this->actingAs($user)->get(route('pegawai.profile.family-members.edit', $member, absolute: false))->assertRedirect(route('pegawai.profile.show', absolute: false));
            $this->actingAs($user)->put(route('pegawai.profile.family-members.update', $member, absolute: false), [
                'full_name' => 'Tidak Diubah', 'relationship' => 'other',
            ])->assertRedirect(route('pegawai.profile.show', absolute: false));
            $this->actingAs($user)->delete(route('pegawai.profile.family-members.destroy', $member, absolute: false))->assertRedirect(route('pegawai.profile.show', absolute: false));

            $this->assertCount(1, $employee->familyMembers);
            $this->assertSame('Anggota Keluarga', $member->refresh()->full_name);
            $this->assertSame($status, $employee->refresh()->verification_status);
        }
    }

    public function test_profile_views_show_empty_state_emergency_contact_and_masked_family_nik(): void
    {
        [$user, $employee] = $this->employeeUser([
            'emergency_contact_name' => 'Kontak Utama',
            'emergency_contact_phone' => '081234567890',
        ]);

        $this->actingAs($user)
            ->get('/pegawai/profile')
            ->assertOk()
            ->assertSee('Kontak Utama')
            ->assertSee('Belum ada data keluarga');

        $member = $this->familyMember($employee, ['nik' => '3201010101011234']);

        $this->actingAs($user)
            ->get('/pegawai/profile')
            ->assertOk()
            ->assertSee('Anggota Keluarga')
            ->assertSee('************1234')
            ->assertDontSee('3201010101011234');

        $this->actingAs($user)
            ->get(route('pegawai.profile.family-members.edit', $member, absolute: false))
            ->assertOk()
            ->assertSee('value="3201010101011234"', false);
    }

    public function test_locked_profile_hides_family_actions_and_emergency_fields_are_optional(): void
    {
        [$user, $employee] = $this->employeeUser(['verification_status' => 'submitted']);
        $this->familyMember($employee);

        $this->actingAs($user)
            ->get('/pegawai/profile')
            ->assertOk()
            ->assertDontSee('Tambah Anggota Keluarga')
            ->assertDontSee('Hapus data anggota keluarga ini?');

        [$draftUser] = $this->employeeUser([], 'optional.emergency@yapista.test');
        $html = $this->actingAs($draftUser)->get('/pegawai/profile/edit')->assertOk()->getContent();

        foreach (['emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone', 'emergency_contact_address'] as $field) {
            $this->assertDoesNotMatchRegularExpression('/name="'.preg_quote($field, '/').'"[^>]*\brequired\b/i', $html);
        }
    }

    /**
     * @param  array<string, mixed>  $employeeOverrides
     * @return array{User, Employee}
     */
    private function employeeUser(array $employeeOverrides = [], string $email = 'family.profile@yapista.test'): array
    {
        $institution = Institution::create(['name' => 'Unit '.uniqid(), 'level' => 'SMK', 'status' => 'active']);
        $position = Position::create(['institution_id' => $institution->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);
        $user = User::factory()->create(['email' => $email, 'role' => 'pegawai', 'status' => 'active']);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Keluarga',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $employeeOverrides));

        return [$user, $employee];
    }

    /** @param array<string, mixed> $overrides */
    private function familyMember(Employee $employee, array $overrides = []): EmployeeFamilyMember
    {
        return $employee->familyMembers()->create(array_merge([
            'full_name' => 'Anggota Keluarga',
            'relationship' => 'child',
        ], $overrides));
    }
}
