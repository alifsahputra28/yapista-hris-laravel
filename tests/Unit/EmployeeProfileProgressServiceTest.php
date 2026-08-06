<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeProfileProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeProfileProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimal_employee_gets_dynamic_progress_without_progress_columns(): void
    {
        $employee = $this->employee();
        $progress = $this->progress($employee);

        $this->assertSame(['identification', 'contact-address', 'family', 'education', 'administration'], array_keys($progress['sections']));
        $this->assertSame(5, $progress['total_sections']);
        $this->assertSame(0, $progress['completed_sections']);
        $this->assertSame('identification', $progress['next_incomplete_step']);
        $this->assertSame(4, $progress['percentage']);
        foreach (['profile_completion_percentage', 'profile_percentage', 'completed_steps', 'profile_is_complete', 'last_profile_step', 'wizard_step'] as $column) {
            $this->assertFalse(Schema::hasColumn('employees', $column));
        }
    }

    public function test_identification_and_contact_percentages_are_computed_per_item(): void
    {
        $employee = $this->employee([
            'nik' => '3201010101010001',
            'family_card_number' => '3201010101010002',
            'birth_place' => 'Bandung',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'religion' => 'islam',
            'marital_status' => 'single',
            'nationality' => 'Indonesia',
            'photo' => 'employees/photos/test.jpg',
            'phone' => '081234567890',
        ]);

        $progress = $this->progress($employee);
        $this->assertTrue($progress['sections']['identification']['completed']);
        $this->assertSame(100, $progress['sections']['identification']['percentage']);
        $this->assertSame(20, $progress['sections']['contact-address']['percentage']);

        $employee->update([
            'whatsapp_number' => '081234567890',
            'identity_address' => 'Alamat KTP',
            'address' => 'Alamat Domisili',
            'domicile_province' => 'Jawa Barat',
            'domicile_city' => 'Bandung',
            'domicile_district' => 'Coblong',
            'domicile_village' => 'Dago',
            'domicile_postal_code' => '40135',
        ]);
        $this->assertTrue($this->progress($employee->fresh())['sections']['contact-address']['completed']);
    }

    public function test_family_progress_applies_marital_status_condition(): void
    {
        $employee = $this->employee([
            'marital_status' => 'married',
            'emergency_contact_name' => 'Kontak Darurat',
            'emergency_contact_relationship' => 'Saudara',
            'emergency_contact_phone' => '081234567890',
        ]);

        $family = $this->progress($employee)['sections']['family'];
        $this->assertFalse($family['completed']);
        $this->assertContains('Tambahkan data pasangan', $family['missing']);

        $employee->familyMembers()->create(['full_name' => 'Pasangan Pegawai', 'relationship' => 'spouse']);
        $this->assertTrue($this->progress($employee->fresh())['sections']['family']['completed']);

        $single = $this->employee([
            'marital_status' => 'single',
            'emergency_contact_name' => 'Kontak',
            'emergency_contact_relationship' => 'Orang Tua',
            'emergency_contact_phone' => '081298765432',
        ], 'progress.single@yapista.test');
        $this->assertTrue($this->progress($single)['sections']['family']['completed']);
    }

    public function test_education_requires_a_highest_record_but_not_certification(): void
    {
        $employee = $this->employee();
        $this->assertSame(0, $this->progress($employee)['sections']['education']['percentage']);

        $education = $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Uji',
        ]);
        $section = $this->progress($employee->fresh())['sections']['education'];
        $this->assertSame(50, $section['percentage']);
        $this->assertContains('Tentukan pendidikan tertinggi', $section['missing']);

        $education->update(['is_highest' => true]);
        $this->assertTrue($this->progress($employee->fresh())['sections']['education']['completed']);
        $this->assertCount(0, $employee->certifications);
    }

    public function test_administration_progress_uses_conditional_tax_and_bpjs_numbers(): void
    {
        $employee = $this->employee();
        $employee->administrativeDetail()->create([
            'bank_name' => 'Bank Uji',
            'bank_account_number' => '001122334455',
            'bank_account_holder' => 'Pegawai Uji',
            'tax_status' => 'registered',
            'bpjs_health_status' => 'active',
            'bpjs_employment_status' => 'not_registered',
        ]);

        $section = $this->progress($employee->fresh())['sections']['administration'];
        $this->assertSame(75, $section['percentage']);
        $this->assertFalse($section['completed']);

        $employee->administrativeDetail->update([
            'nik_used_as_tax_id' => true,
            'bpjs_health_number' => '001122334466',
        ]);
        $this->assertTrue($this->progress($employee->fresh())['sections']['administration']['completed']);
    }

    public function test_overall_is_rounded_average_and_result_contains_no_sensitive_values(): void
    {
        $employee = $this->employee([
            'nik' => '3201010101019999',
            'family_card_number' => '3201010101018888',
            'identity_address' => 'Alamat Rahasia Pegawai',
        ]);
        $employee->administrativeDetail()->create([
            'bank_account_number' => '001122334455',
            'tax_identification_number' => '0011223344556677',
            'bpjs_health_number' => '001122334466',
        ]);

        $progress = $this->progress($employee);
        $expected = (int) round(collect($progress['sections'])->avg('percentage'));
        $this->assertSame($expected, $progress['percentage']);
        $serialized = json_encode($progress, JSON_THROW_ON_ERROR);
        foreach (['3201010101019999', '3201010101018888', 'Alamat Rahasia Pegawai', '001122334455', '0011223344556677', '001122334466'] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
    }

    private function progress(Employee $employee): array
    {
        return app(EmployeeProfileProgressService::class)->calculate($employee);
    }

    /** @param array<string, mixed> $overrides */
    private function employee(array $overrides = [], string $email = 'profile.progress@yapista.test'): Employee
    {
        $institution = Institution::create(['name' => 'Unit '.uniqid(), 'level' => 'SMK', 'status' => 'active']);
        $position = Position::create(['institution_id' => $institution->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);
        $user = User::factory()->create(['email' => $email, 'role' => 'pegawai', 'status' => 'active']);

        return Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Progress',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $overrides));
    }
}
