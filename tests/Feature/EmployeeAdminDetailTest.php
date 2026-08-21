<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeProfileProgressService;
use App\Services\EmployeeQrTokenService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAdminDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_existing_employee_keeps_official_state_and_all_profile_sections_visible(): void
    {
        $admin = $this->user('super_admin');
        $employee = $this->employee([
            'employee_number' => '0123456789',
            'verification_status' => 'verified',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);
        app(EmployeeQrTokenService::class)->generate($employee, $admin);

        $activeQrCount = $employee->activeQrToken()->count();

        $this->actingAs($admin)
            ->get(route('employees.show', $employee, absolute: false))
            ->assertOk()
            ->assertSeeText('Informasi Pribadi')
            ->assertSeeText('Kepegawaian')
            ->assertSeeText('Kontak & Alamat')
            ->assertSeeText('Kontak Darurat')
            ->assertSeeText('Keluarga')
            ->assertSeeText('Pendidikan')
            ->assertSeeText('Sertifikasi')
            ->assertSeeText('Administrasi, Jaminan & Rekening Bank')
            ->assertSeeText('Dokumen Pegawai')
            ->assertSeeText('Verifikasi')
            ->assertSeeText('Kelengkapan Profil')
            ->assertSeeText('Belum diisi')
            ->assertSeeText('Belum ada data keluarga.')
            ->assertSeeText('Belum ada data pendidikan.')
            ->assertSeeText('Belum ada data sertifikasi.')
            ->assertSeeText('Belum ada dokumen pegawai.')
            ->assertSeeText('0123456789')
            ->assertSeeText('Aktif')
            ->assertSeeText('Terverifikasi')
            ->assertSeeText('Data tambahan bersifat opsional');

        $this->assertSame('verified', $employee->fresh()->verification_status);
        $this->assertTrue($employee->fresh()->isEligibleForIdCard());
        $this->assertSame($activeQrCount, $employee->activeQrToken()->count());
    }

    public function test_complete_profile_displays_all_authorized_values_and_masks_sensitive_numbers(): void
    {
        $admin = $this->user('hr_admin');
        $employeeUser = $this->user('pegawai', ['email' => 'login.complete@yapista.test']);
        $employee = $this->employee([
            'user_id' => $employeeUser->id,
            'employee_number' => '0123400001',
            'full_name' => 'Pegawai Profil Lengkap',
            'email' => 'pribadi.complete@yapista.test',
            'nik' => '3201010101010001',
            'family_card_number' => '3201010101010002',
            'gender' => 'female',
            'birth_place' => 'Batam',
            'birth_date' => '1991-02-03',
            'religion' => 'islam',
            'marital_status' => 'married',
            'nationality' => 'Indonesia',
            'blood_type' => 'AB',
            'phone' => '081234567801',
            'whatsapp_number' => '081234567802',
            'identity_address' => 'Alamat KTP Sintetis',
            'domicile_same_as_identity' => false,
            'address' => 'Alamat Domisili Sintetis',
            'domicile_province' => 'Kepulauan Riau',
            'domicile_city' => 'Batam',
            'domicile_district' => 'Sekupang',
            'domicile_village' => 'Tiban Indah',
            'domicile_postal_code' => '29426',
            'emergency_contact_name' => 'Kontak Darurat Sintetis',
            'emergency_contact_relationship' => 'Saudara',
            'emergency_contact_phone' => '081234567803',
            'emergency_contact_address' => 'Alamat Kontak Sintetis',
            'join_date' => '2020-07-01',
            'photo' => 'employees/photos/synthetic-profile.jpg',
            'verification_status' => 'verified',
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'verification_note' => 'Data utama telah diverifikasi.',
        ]);
        $employee->forceFill([
            'profile_review_status' => Employee::PROFILE_REVIEW_APPROVED,
            'profile_submitted_at' => now()->subDay(),
            'profile_reviewed_at' => now(),
            'profile_reviewed_by' => $admin->id,
            'profile_review_note' => 'Data tambahan telah direview.',
        ])->save();

        $familyMember = $employee->familyMembers()->create([
            'full_name' => 'Pasangan Sintetis',
            'relationship' => 'spouse',
            'nik' => '3201010101010003',
            'birth_place' => 'Batam',
            'birth_date' => '1992-04-05',
            'gender' => 'male',
            'occupation' => 'Wiraswasta',
            'is_dependent' => true,
            'bpjs_status' => 'active',
        ]);
        $education = $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Sintetis',
            'major' => 'Sistem Informasi',
            'start_year' => 2009,
            'graduation_year' => 2013,
            'certificate_number' => 'IJAZAH-SYNTH-0004',
            'degree_prefix' => 'Drs.',
            'degree_suffix' => 'S.Kom.',
            'is_highest' => true,
        ]);
        $certification = $employee->certifications()->create([
            'name' => 'Sertifikasi Kompetensi Sintetis',
            'certificate_number' => 'SERTIFIKAT-SYNTH-0005',
            'issuer' => 'Lembaga Sertifikasi Sintetis',
            'competency_field' => 'Teknologi Informasi',
            'issued_at' => now()->subYear(),
            'expired_at' => now()->addYear(),
            'is_active' => true,
        ]);
        $administration = $employee->administrativeDetail()->create([
            'bank_name' => 'Bank Sintetis',
            'bank_account_number' => '123456789006',
            'bank_account_holder' => 'Pegawai Profil Lengkap',
            'tax_status' => 'registered',
            'tax_identification_number' => '123456789010007',
            'nik_used_as_tax_id' => false,
            'ptkp_status' => 'K/1',
            'bpjs_health_status' => 'active',
            'bpjs_health_number' => '123456780008',
            'bpjs_employment_status' => 'active',
            'bpjs_employment_number' => '123456780009',
        ]);
        $document = $employee->documents()->create([
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/private-synthetic.pdf',
            'original_name' => 'dokumen-sintetis.pdf',
            'status' => 'valid',
            'note' => 'Dokumen tervalidasi.',
            'uploaded_at' => now(),
        ]);
        $qrToken = app(EmployeeQrTokenService::class)->generate($employee, $admin);
        $rawQrPayload = app(EmployeeQrTokenService::class)->payloadFor($qrToken);
        $expectedProgress = app(EmployeeProfileProgressService::class)->calculate($employee->fresh())['percentage'];

        $response = $this->actingAs($admin)
            ->get(route('employees.show', $employee, absolute: false))
            ->assertOk()
            ->assertSeeText('Pegawai Profil Lengkap')
            ->assertSeeText('0123400001')
            ->assertSeeText('login.complete@yapista.test')
            ->assertSeeText('pribadi.complete@yapista.test')
            ->assertSeeText('Alamat KTP Sintetis')
            ->assertSeeText('Alamat Domisili Sintetis')
            ->assertSeeText('Kontak Darurat Sintetis')
            ->assertSeeText('Pasangan Sintetis')
            ->assertSeeText('Universitas Sintetis')
            ->assertSeeText('Sistem Informasi')
            ->assertSeeText('Sertifikasi Kompetensi Sintetis')
            ->assertSeeText('Bank Sintetis')
            ->assertSeeText('dokumen-sintetis.pdf')
            ->assertSeeText('Data tambahan telah direview.')
            ->assertSeeText($expectedProgress.'%');

        foreach ([
            $employee->fresh()->masked_nik,
            $employee->fresh()->masked_family_card_number,
            $familyMember->fresh()->masked_nik,
            $education->fresh()->masked_certificate_number,
            $certification->fresh()->masked_certificate_number,
            $administration->fresh()->masked_bank_account_number,
            $administration->fresh()->masked_tax_identification_number,
            $administration->fresh()->masked_bpjs_health_number,
            $administration->fresh()->masked_bpjs_employment_number,
        ] as $maskedValue) {
            $response->assertSeeText($maskedValue);
        }

        foreach ([
            '3201010101010001',
            '3201010101010002',
            '3201010101010003',
            'IJAZAH-SYNTH-0004',
            'SERTIFIKAT-SYNTH-0005',
            '123456789006',
            '123456789010007',
            '123456780008',
            '123456780009',
            $rawQrPayload,
            $qrToken->token_hash,
            $document->file_path,
            'nik_encrypted',
            'nik_lookup',
        ] as $sensitiveValue) {
            $response->assertDontSee($sensitiveValue, escape: false);
        }
    }

    public function test_employee_detail_authorization_matches_admin_hr_boundary(): void
    {
        $employee = $this->employee();

        foreach (['super_admin', 'hr_admin'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('employees.show', $employee, absolute: false))
                ->assertOk();
        }

        foreach (['pegawai', 'panitia'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('employees.show', $employee, absolute: false))
                ->assertForbidden();
        }

        $this->app['auth']->forgetGuards();
        $this->get(route('employees.show', $employee, absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_employee_detail_uses_bounded_eager_loaded_queries(): void
    {
        $admin = $this->user('super_admin');
        $employee = $this->employee();

        foreach (range(1, 5) as $index) {
            $employee->familyMembers()->create([
                'full_name' => 'Keluarga Query '.$index,
                'relationship' => 'child',
            ]);
            $employee->educations()->create([
                'education_level' => 'sarjana',
                'institution_name' => 'Pendidikan Query '.$index,
            ]);
            $employee->certifications()->create([
                'name' => 'Sertifikasi Query '.$index,
                'is_active' => true,
            ]);
            $employee->documents()->create([
                'document_type' => 'dokumen_lain_'.$index,
                'file_path' => 'employees/documents/query-'.$index.'.pdf',
                'status' => 'pending',
            ]);
        }

        $selectQueries = 0;
        $capturing = false;
        $this->app['db']->listen(function (QueryExecuted $query) use (&$selectQueries, &$capturing): void {
            if ($capturing && str_starts_with(strtolower(ltrim($query->sql)), 'select')) {
                $selectQueries++;
            }
        });

        $capturing = true;
        try {
            $this->actingAs($admin)
                ->get(route('employees.show', $employee, absolute: false))
                ->assertOk();
        } finally {
            $capturing = false;
        }

        $this->assertLessThanOrEqual(14, $selectQueries, "Employee detail menjalankan {$selectQueries} SELECT queries.");
    }

    /** @param array<string, mixed> $overrides */
    private function employee(array $overrides = []): Employee
    {
        $institution = Institution::create([
            'name' => 'Unit Detail '.uniqid(),
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Jabatan Detail '.uniqid(),
            'type' => 'administratif',
            'status' => 'active',
        ]);

        return Employee::create(array_merge([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Detail Sintetis',
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'status' => 'active',
        ], $overrides));
    }
}
