<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeNikProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeNikSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();
        $this->institution = Institution::create(['name' => 'Unit NIK', 'level' => 'SMK', 'status' => 'active']);
        $this->position = Position::create(['institution_id' => $this->institution->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);
    }

    public function test_model_stores_encrypted_nik_lookup_and_hides_sensitive_storage(): void
    {
        $nik = '3201010101010001';
        $employee = $this->employee(['nik' => $nik]);
        $raw = DB::table('employees')->where('id', $employee->id)->first();

        $this->assertNull($raw->nik);
        $this->assertNotNull($raw->nik_encrypted);
        $this->assertNotSame($nik, $raw->nik_encrypted);
        $this->assertSame(app(EmployeeNikProtectionService::class)->lookup($nik), $raw->nik_lookup);
        $this->assertNotNull($raw->nik_migrated_at);
        $this->assertSame($nik, $employee->fresh()->nik);
        $this->assertSame('************0001', $employee->fresh()->masked_nik);

        $serialized = $employee->fresh()->toArray();
        $this->assertArrayNotHasKey('nik', $serialized);
        $this->assertArrayNotHasKey('nik_encrypted', $serialized);
        $this->assertArrayNotHasKey('nik_lookup', $serialized);
        $this->assertArrayNotHasKey('nik_migrated_at', $serialized);
    }

    public function test_employee_without_nik_remains_valid_and_clearing_nik_clears_secure_fields(): void
    {
        $employee = $this->employee();
        $this->assertNull($employee->nik);

        $employee->nik = '3201010101010002';
        $employee->save();
        $employee->nik = null;
        $employee->save();

        $raw = DB::table('employees')->where('id', $employee->id)->first();
        $this->assertNull($raw->nik);
        $this->assertNull($raw->nik_encrypted);
        $this->assertNull($raw->nik_lookup);
        $this->assertNull($raw->nik_migrated_at);
    }

    public function test_unique_validation_uses_blind_index_and_allows_own_nik_on_update(): void
    {
        $admin = User::factory()->create(['role' => 'hr_admin', 'status' => 'active']);
        $nik = '3201010101010003';
        $employee = $this->employee(['nik' => $nik]);

        $this->actingAs($admin)
            ->post(route('employees.store', absolute: false), $this->employeePayload('Pegawai Duplikat', '7770950002', $nik))
            ->assertSessionHasErrors(['nik' => 'NIK sudah digunakan oleh pegawai lain.']);

        $this->actingAs($admin)
            ->put(route('employees.update', $employee, absolute: false), $this->employeePayload($employee->full_name, $employee->employee_number, $nik))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('employees.index', absolute: false));

        $this->actingAs($admin)
            ->post(route('employees.store', absolute: false), $this->employeePayload('Pegawai Berbeda', '7770950003', '3201010101010004'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Employee::count());
        $this->assertSame(0, Employee::whereNotNull('nik')->count());
    }

    public function test_exact_nik_search_uses_post_and_general_search_never_uses_partial_nik(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $employee = $this->employee(['full_name' => 'Pegawai Rahasia', 'employee_number' => '7770950004', 'nik' => '3201010101011234']);

        $this->actingAs($admin)
            ->post(route('employees.nik-search', absolute: false), ['nik' => '3201010101011234'])
            ->assertRedirect(route('employees.show', $employee, absolute: false));

        $this->actingAs($admin)
            ->get(route('employees.index', ['search' => '1234'], absolute: false))
            ->assertOk()
            ->assertDontSee('Pegawai Rahasia');

        $this->actingAs($admin)
            ->get(route('employees.index', ['search' => 'Pegawai Rahasia'], absolute: false))
            ->assertOk()
            ->assertSee('Pegawai Rahasia');

        $this->actingAs($admin)
            ->get(route('employees.index', ['search' => '7770950004'], absolute: false))
            ->assertOk()
            ->assertSee('Pegawai Rahasia');
    }

    public function test_nik_search_authorization_and_errors_do_not_flash_or_display_nik(): void
    {
        $route = route('employees.nik-search', absolute: false);
        $nik = '3201010101018888';

        $this->post($route, ['nik' => $nik])->assertRedirect(route('login', absolute: false));

        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        $this->actingAs($panitia)->post($route, ['nik' => $nik])->assertForbidden();

        $admin = User::factory()->create(['role' => 'hr_admin', 'status' => 'active']);
        $response = $this->actingAs($admin)->post($route, ['nik' => $nik]);
        $response->assertRedirect()->assertSessionHas('error')->assertSessionMissing('_old_input');
        $this->assertStringNotContainsString($nik, (string) session('error'));
    }

    public function test_non_edit_pages_mask_nik_while_authorized_edit_forms_can_show_it(): void
    {
        $nik = '3201010101015678';
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'pegawai', 'status' => 'active']);
        $employee = $this->employee(['user_id' => $user->id, 'nik' => $nik, 'verification_status' => 'draft']);

        $this->actingAs($admin)->get(route('employees.show', $employee, absolute: false))
            ->assertOk()->assertSee('************5678')->assertDontSee($nik);
        $this->actingAs($admin)->get(route('employees.edit', $employee, absolute: false))
            ->assertOk()->assertSee($nik);
        $this->actingAs($admin)->get(route('verifications.show', $employee, absolute: false))
            ->assertOk()->assertSee('************5678')->assertDontSee($nik);
        $this->actingAs($user)->get(route('pegawai.profile.show', absolute: false))
            ->assertOk()->assertSee('************5678')->assertDontSee($nik);
        $this->actingAs($user)->get(route('pegawai.profile.edit', absolute: false))
            ->assertOk()->assertSee($nik);
        $this->actingAs($user)->get(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertOk()->assertSee('************5678')->assertDontSee($nik);
    }

    public function test_reports_exports_and_id_card_do_not_expose_nik_but_keep_nup(): void
    {
        $nik = '3201010101017777';
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $employee = $this->employee([
            'nik' => $nik,
            'employee_number' => '7770950005',
            'verification_status' => 'verified',
        ]);

        $this->actingAs($admin)->get(route('reports.employees', absolute: false))
            ->assertOk()->assertDontSee($nik)->assertSee('7770950005');

        $export = $this->actingAs($admin)->get(route('reports.employees.export', absolute: false));
        $this->assertStringNotContainsString($nik, $export->streamedContent());

        $this->actingAs($admin)->get(route('employees.id-card.show', $employee, absolute: false))
            ->assertOk()->assertDontSee($nik)->assertSee('7770950005');
    }

    public function test_backfill_dry_run_commit_and_second_run_are_safe_and_idempotent(): void
    {
        $employee = $this->employee([
            'employee_number' => '7770950006',
            'verification_status' => 'submitted',
            'phone' => '081234567890',
        ]);
        $nik = '3201010101019001';
        DB::table('employees')->where('id', $employee->id)->update(['nik' => $nik]);

        $this->assertSame(0, Artisan::call('employee-security:backfill-nik', ['--dry-run' => true]));
        $dryRunOutput = Artisan::output();
        $this->assertStringContainsString('Dry-run selesai', $dryRunOutput);
        $this->assertStringNotContainsString($nik, $dryRunOutput);
        $this->assertSame($nik, DB::table('employees')->where('id', $employee->id)->value('nik'));

        $this->assertSame(0, Artisan::call('employee-security:backfill-nik', ['--commit' => true]));
        $raw = DB::table('employees')->where('id', $employee->id)->first();
        $this->assertNull($raw->nik);
        $this->assertNotNull($raw->nik_encrypted);
        $this->assertNotNull($raw->nik_lookup);
        $this->assertNotNull($raw->nik_migrated_at);
        $this->assertSame('7770950006', $employee->fresh()->employee_number);
        $this->assertSame('submitted', $employee->fresh()->verification_status);
        $this->assertSame('081234567890', $employee->fresh()->phone);

        $ciphertext = $raw->nik_encrypted;
        $this->assertSame(0, Artisan::call('employee-security:backfill-nik', ['--commit' => true]));
        $this->assertSame($ciphertext, DB::table('employees')->where('id', $employee->id)->value('nik_encrypted'));
        $this->assertSame(0, Artisan::call('employee-security:verify-nik'));
    }

    public function test_backfill_reports_invalid_and_duplicate_conflicts_without_exposing_values(): void
    {
        $protected = $this->employee(['nik' => '3201010101019002']);
        $conflict = $this->employee(['employee_number' => '7770950007']);
        $invalid = $this->employee(['employee_number' => '7770950008']);
        DB::table('employees')->where('id', $conflict->id)->update(['nik' => '3201010101019002']);
        DB::table('employees')->where('id', $invalid->id)->update(['nik' => 'INVALID-NIK']);

        $this->assertSame(0, Artisan::call('employee-security:backfill-nik', ['--commit' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('Invalid format', $output);
        $this->assertStringContainsString('Duplicate conflict', $output);
        $this->assertStringNotContainsString('3201010101019002', $output);
        $this->assertStringNotContainsString('INVALID-NIK', $output);
        $this->assertSame('3201010101019002', DB::table('employees')->where('id', $conflict->id)->value('nik'));
        $this->assertSame('INVALID-NIK', DB::table('employees')->where('id', $invalid->id)->value('nik'));
        $this->assertSame('3201010101019002', $protected->fresh()->nik);
    }

    public function test_verification_command_reports_corrupt_ciphertext_without_exposing_nik(): void
    {
        $employee = $this->employee(['nik' => '3201010101019003']);
        DB::table('employees')->where('id', $employee->id)->update(['nik_encrypted' => 'corrupt-ciphertext']);

        $this->assertSame(1, Artisan::call('employee-security:verify-nik'));
        $output = Artisan::output();
        $this->assertStringContainsString('decrypt_failed', $output);
        $this->assertStringContainsString($employee->employee_number, $output);
        $this->assertStringNotContainsString('3201010101019003', $output);
        $this->assertStringNotContainsString('corrupt-ciphertext', $output);
    }

    /** @param array<string, mixed> $overrides */
    private function employee(array $overrides = []): Employee
    {
        static $sequence = 0;
        $sequence++;

        return Employee::create(array_merge([
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'employee_number' => '777096'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'full_name' => 'Pegawai NIK '.$sequence,
            'email' => "pegawai.nik.{$sequence}@yapista.test",
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function employeePayload(string $name, string $employeeNumber, string $nik): array
    {
        return [
            'full_name' => $name,
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'employee_number' => $employeeNumber,
            'nik' => $nik,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
        ];
    }
}
