<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAdministrativeDetail;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeAdministrativeDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_employee_may_have_no_administrative_detail_and_get_does_not_create_one(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->assertArrayNotHasKey('administrative_detail', $employee->toArray());
        $this->assertNull($employee->administrativeDetail);

        $this->actingAs($user)
            ->get(route('pegawai.profile.administrative-details.edit', absolute: false))
            ->assertOk()
            ->assertSee('Simpan Data Administrasi');

        $this->assertDatabaseCount('employee_administrative_details', 0);
    }

    public function test_relation_is_one_to_one_allows_null_fields_and_cascades_on_employee_delete(): void
    {
        [, $employee] = $this->employeeUser();
        $detail = $employee->administrativeDetail()->create([]);

        $this->assertSame($employee->id, $detail->employee->id);
        $this->assertNull($detail->bank_name);
        $this->assertNull($detail->nik_used_as_tax_id);

        try {
            DB::table('employee_administrative_details')->insert([
                'employee_id' => $employee->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('Unique employee_id seharusnya menolak record kedua.');
        } catch (QueryException) {
            $this->assertDatabaseCount('employee_administrative_details', 1);
        }

        $employee->delete();
        $this->assertDatabaseMissing('employee_administrative_details', ['id' => $detail->id]);
    }

    public function test_sensitive_numbers_are_encrypted_decrypted_and_hidden_from_serialization(): void
    {
        [, $employee] = $this->employeeUser();
        $values = [
            'bank_account_number' => '001234567890',
            'tax_identification_number' => '0123456789012345',
            'bpjs_health_number' => '0001234567890',
            'bpjs_employment_number' => '0009876543210',
        ];
        $detail = $employee->administrativeDetail()->create($values + ['nik_used_as_tax_id' => false]);
        $raw = (array) DB::table('employee_administrative_details')->where('id', $detail->id)->first();

        foreach ($values as $field => $plainValue) {
            $this->assertNotSame($plainValue, $raw[$field]);
            $this->assertSame($plainValue, $detail->refresh()->{$field});
            $this->assertArrayNotHasKey($field, $detail->toArray());
        }

        $this->assertFalse($detail->nik_used_as_tax_id);
    }

    public function test_masking_keeps_only_last_four_characters_and_handles_null_or_short_values(): void
    {
        [, $employee] = $this->employeeUser();
        $detail = $employee->administrativeDetail()->create([
            'bank_account_number' => '001234567890',
            'tax_identification_number' => '0123456789012345',
            'bpjs_health_number' => '123',
            'bpjs_employment_number' => null,
        ]);

        $this->assertSame('********7890', $detail->masked_bank_account_number);
        $this->assertSame('************2345', $detail->masked_tax_identification_number);
        $this->assertSame('***', $detail->masked_bpjs_health_number);
        $this->assertNull($detail->masked_bpjs_employment_number);
    }

    public function test_first_and_partial_updates_create_one_record_without_changing_profile_status(): void
    {
        [$user, $employee] = $this->employeeUser();
        $route = route('pegawai.profile.administrative-details.update', absolute: false);

        $this->actingAs($user)->put($route, ['bank_name' => 'Bank Syariah Indonesia'])
            ->assertRedirect(route('pegawai.profile.show', absolute: false))
            ->assertSessionHasNoErrors();
        $this->assertSame('Bank Syariah Indonesia', $employee->administrativeDetail()->firstOrFail()->bank_name);

        $this->actingAs($user)->patch($route, ['tax_status' => 'registered'])->assertSessionHasNoErrors();
        $this->actingAs($user)->put($route, ['bpjs_health_status' => 'active'])->assertSessionHasNoErrors();

        $detail = $employee->administrativeDetail()->firstOrFail();
        $this->assertSame('Bank Syariah Indonesia', $detail->bank_name);
        $this->assertSame('registered', $detail->tax_status);
        $this->assertSame('active', $detail->bpjs_health_status);
        $this->assertSame('draft', $employee->refresh()->verification_status);
        $this->assertDatabaseCount('employee_administrative_details', 1);

        [, $emptyEmployee] = $this->employeeUser([], 'administrative.empty@yapista.test');
        $emptyUser = $emptyEmployee->user;
        $this->actingAs($emptyUser)->put($route, [])->assertSessionHasNoErrors();
        $this->assertNotNull($emptyEmployee->administrativeDetail()->first());
    }

    public function test_rejected_employee_can_update_and_remains_rejected(): void
    {
        [$user, $employee] = $this->employeeUser(
            ['verification_status' => 'rejected'],
            'administrative.rejected@yapista.test',
        );

        $this->actingAs($user)
            ->put(route('pegawai.profile.administrative-details.update', absolute: false), ['ptkp_status' => 'K/1'])
            ->assertSessionHasNoErrors();

        $this->assertSame('K/1', $employee->administrativeDetail()->firstOrFail()->ptkp_status);
        $this->assertSame('rejected', $employee->refresh()->verification_status);
    }

    public function test_valid_numbers_are_normalized_without_losing_leading_zeroes(): void
    {
        [$user, $employee] = $this->employeeUser();
        $route = route('pegawai.profile.administrative-details.update', absolute: false);

        $this->actingAs($user)->put($route, [
            'bank_account_number' => '0012 3456 7890',
            'tax_identification_number' => '01.234.567.890-1234',
            'bpjs_health_number' => '0001 2345 6789',
            'bpjs_employment_number' => '0009 8765 4321',
            'nik_used_as_tax_id' => '0',
        ])->assertSessionHasNoErrors();

        $detail = $employee->administrativeDetail()->firstOrFail();
        $this->assertSame('001234567890', $detail->bank_account_number);
        $this->assertSame('012345678901234', $detail->tax_identification_number);
        $this->assertSame('000123456789', $detail->bpjs_health_number);
        $this->assertSame('000987654321', $detail->bpjs_employment_number);
        $this->assertFalse($detail->nik_used_as_tax_id);

        $this->actingAs($user)
            ->get(route('pegawai.profile.administrative-details.edit', absolute: false))
            ->assertOk()
            ->assertSee('<option value="0" selected>Tidak</option>', false);

        $this->actingAs($user)->patch($route, ['tax_identification_number' => '0123456789012345'])
            ->assertSessionHasNoErrors();
        $this->assertSame('0123456789012345', $detail->refresh()->tax_identification_number);
    }

    public function test_invalid_numbers_and_canonical_options_are_rejected(): void
    {
        [$user, $employee] = $this->employeeUser();
        $route = route('pegawai.profile.administrative-details.update', absolute: false);

        $this->actingAs($user)->put($route, [
            'bank_account_number' => '12ABC',
            'tax_status' => 'unknown',
            'tax_identification_number' => '1234',
            'ptkp_status' => 'K/9',
            'bpjs_health_status' => 'unknown',
            'bpjs_health_number' => 'BPJS12345',
            'bpjs_employment_status' => 'unknown',
            'bpjs_employment_number' => 'KPJ12345',
        ])->assertSessionHasErrors([
            'bank_account_number',
            'tax_status',
            'tax_identification_number',
            'ptkp_status',
            'bpjs_health_status',
            'bpjs_health_number',
            'bpjs_employment_status',
            'bpjs_employment_number',
        ]);

        $this->assertNull($employee->administrativeDetail()->first());
    }

    public function test_guest_panitia_and_locked_profiles_cannot_edit_or_update(): void
    {
        $editRoute = route('pegawai.profile.administrative-details.edit', absolute: false);
        $updateRoute = route('pegawai.profile.administrative-details.update', absolute: false);
        $this->get($editRoute)->assertRedirect('/login');
        $this->put($updateRoute, ['bank_name' => 'Guest'])->assertRedirect('/login');

        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        $this->actingAs($panitia)->get($editRoute)->assertForbidden();
        $this->actingAs($panitia)->put($updateRoute, ['bank_name' => 'Panitia'])->assertForbidden();

        foreach (['submitted', 'verified'] as $status) {
            [$user, $employee] = $this->employeeUser(
                ['verification_status' => $status],
                "administrative.locked.{$status}@yapista.test",
            );
            $profileRoute = route('pegawai.profile.show', absolute: false);

            $this->actingAs($user)->get($editRoute)->assertRedirect($profileRoute);
            $this->actingAs($user)->put($updateRoute, ['bank_name' => 'Tidak Disimpan'])->assertRedirect($profileRoute);
            $this->assertNull($employee->administrativeDetail()->first());
            $this->assertSame($status, $employee->refresh()->verification_status);
        }
    }

    public function test_employee_id_manipulation_is_ignored_and_hr_fields_are_unchanged(): void
    {
        [$user, $employee] = $this->employeeUser(['employee_number' => '7770945001']);
        [, $otherEmployee] = $this->employeeUser([], 'administrative.other@yapista.test');
        DB::table('employees')->where('id', $employee->id)->update([
            'nup' => 'LEGACY-NUP',
            'foundation_registry_number' => 9876,
        ]);
        $employee->refresh();
        $original = $employee->only([
            'employee_number', 'institution_id', 'position_id', 'employment_status',
            'verification_status', 'user_id', 'nup', 'foundation_registry_number',
        ]);
        $originalUserEmail = $user->email;

        $this->actingAs($user)->put(route('pegawai.profile.administrative-details.update', absolute: false), [
            'bank_name' => 'Bank Pemilik',
            'employee_id' => $otherEmployee->id,
            'employee_number' => '7770945999',
            'institution_id' => $otherEmployee->institution_id,
            'position_id' => $otherEmployee->position_id,
            'employment_status' => 'resign',
            'verification_status' => 'verified',
            'email' => 'changed@yapista.test',
            'nup' => 'CHANGED',
            'foundation_registry_number' => 1234,
        ])->assertSessionHasNoErrors();

        $this->assertSame($employee->id, $employee->administrativeDetail()->firstOrFail()->employee_id);
        $this->assertNull($otherEmployee->administrativeDetail()->first());
        $this->assertSame($original, $employee->refresh()->only(array_keys($original)));
        $this->assertSame($originalUserEmail, $user->refresh()->email);
    }

    public function test_profile_masks_values_edit_form_decrypts_them_and_locked_profile_hides_action(): void
    {
        [$user, $employee] = $this->employeeUser();
        $numbers = $this->sensitiveNumbers();

        $this->actingAs($user)->get('/pegawai/profile')
            ->assertOk()
            ->assertSee('Data Bank, Pajak, dan BPJS')
            ->assertSee('Belum diisi')
            ->assertSee('Edit Data Administrasi');

        $detail = $employee->administrativeDetail()->create($numbers + [
            'bank_name' => 'Bank Uji',
            'tax_status' => 'registered',
            'bpjs_health_status' => 'active',
        ]);
        $show = $this->actingAs($user)->get('/pegawai/profile')->assertOk();

        foreach ($numbers as $plainValue) {
            $show->assertDontSee($plainValue);
        }
        $show->assertSee($detail->masked_bank_account_number)
            ->assertSee($detail->masked_tax_identification_number)
            ->assertSee($detail->masked_bpjs_health_number)
            ->assertSee($detail->masked_bpjs_employment_number);

        $edit = $this->actingAs($user)
            ->get(route('pegawai.profile.administrative-details.edit', absolute: false))
            ->assertOk()
            ->assertDontSee('type="number"', false)
            ->assertDontSee('type="file"', false);
        foreach ($numbers as $plainValue) {
            $edit->assertSee('value="'.$plainValue.'"', false);
        }

        $employee->update(['verification_status' => 'verified', 'employee_number' => '7770945111']);
        $user->unsetRelation('employee');
        $this->actingAs($user)->get('/pegawai/profile')
            ->assertOk()
            ->assertDontSee('Edit Data Administrasi');
    }

    public function test_sensitive_administrative_data_does_not_appear_on_id_card_scanner_or_employee_export(): void
    {
        [$employeeUser, $employee] = $this->employeeUser([
            'employee_number' => '7770945222',
            'verification_status' => 'verified',
        ], 'administrative.privacy@yapista.test');
        $numbers = $this->sensitiveNumbers();
        $employee->administrativeDetail()->create($numbers);

        $idCard = $this->actingAs($employeeUser)->get(route('pegawai.id-card.show', absolute: false))->assertOk();
        foreach ($numbers as $plainValue) {
            $idCard->assertDontSee($plainValue);
        }

        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $event = Event::create([
            'name' => 'Kegiatan Privasi',
            'event_date' => today(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'location' => 'Aula',
            'target_type' => 'all',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $scanner = $this->actingAs($admin)->get(route('events.scanner', $event, absolute: false))->assertOk();
        foreach ($numbers as $plainValue) {
            $scanner->assertDontSee($plainValue);
        }

        $export = $this->actingAs($admin)->get(route('reports.employees.export', absolute: false))->assertOk();
        $exportContent = $export->streamedContent();
        foreach ($numbers as $plainValue) {
            $this->assertStringNotContainsString($plainValue, $exportContent);
        }
    }

    /**
     * @param  array<string, mixed>  $employeeOverrides
     * @return array{User, Employee}
     */
    private function employeeUser(array $employeeOverrides = [], string $email = 'administrative.profile@yapista.test'): array
    {
        $institution = Institution::create(['name' => 'Unit '.uniqid(), 'level' => 'SMK', 'status' => 'active']);
        $position = Position::create(['institution_id' => $institution->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);
        $user = User::factory()->create(['email' => $email, 'role' => 'pegawai', 'status' => 'active']);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Administrasi',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $employeeOverrides));

        return [$user, $employee];
    }

    /** @return array<string, string> */
    private function sensitiveNumbers(): array
    {
        return [
            'bank_account_number' => '001122334455',
            'tax_identification_number' => '0011223344556677',
            'bpjs_health_number' => '001122334466',
            'bpjs_employment_number' => '001122334477',
        ];
    }
}
