<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeProfileSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeProfileSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('public');
    }

    public function test_profile_review_fields_have_safe_defaults_casts_and_editability_rules(): void
    {
        [, $employee] = $this->employeeUser();

        $employee->refresh();
        $this->assertSame(Employee::PROFILE_REVIEW_DRAFT, $employee->profile_review_status);
        $this->assertNull($employee->profile_submitted_at);
        $this->assertTrue($employee->canEditProfileCompletion());

        $employee->forceFill([
            'profile_review_status' => Employee::PROFILE_REVIEW_REJECTED,
            'profile_reviewed_at' => now(),
            'profile_rejected_sections' => ['documents', 'education'],
        ])->save();

        $employee->refresh();
        $this->assertTrue($employee->canEditProfileCompletion());
        $this->assertTrue($employee->profile_reviewed_at->isToday());
        $this->assertSame(['documents', 'education'], $employee->profile_rejected_sections);

        $employee->forceFill(['profile_review_status' => Employee::PROFILE_REVIEW_SUBMITTED])->save();
        $this->assertFalse($employee->fresh()->canEditProfileCompletion());

        $employee->forceFill([
            'verification_status' => 'verified',
            'profile_review_status' => Employee::PROFILE_REVIEW_DRAFT,
        ])->save();
        $this->assertFalse($employee->fresh()->canEditProfileCompletion());
    }

    public function test_document_slots_support_private_replace_repeated_records_and_owned_targets(): void
    {
        [$user, $employee] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser([], 'other-slots@yapista.test');
        $educationOne = $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Satu',
            'is_highest' => true,
        ]);
        $educationTwo = $employee->educations()->create([
            'education_level' => 'magister',
            'institution_name' => 'Universitas Dua',
        ]);
        $otherEducation = $otherEmployee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Lain',
            'is_highest' => true,
        ]);
        $certificationOne = $employee->certifications()->create(['name' => 'Sertifikasi Satu']);
        $certificationTwo = $employee->certifications()->create(['name' => 'Sertifikasi Dua']);
        $otherCertification = $otherEmployee->certifications()->create(['name' => 'Sertifikasi Lain']);

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'employee_id' => $otherEmployee->id,
            'file' => UploadedFile::fake()->create('ktp-awal.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $ktp = $employee->documents()->where('document_type', 'ktp')->firstOrFail();
        $oldPath = $ktp->file_path;
        $this->assertSame('primary', $ktp->document_slot);
        $this->assertSame($employee->id, $ktp->employee_id);
        Storage::disk('private')->assertExists($oldPath);
        Storage::disk('public')->assertMissing($oldPath);

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->image('ktp-baru.jpg'),
        ])->assertRedirect();

        $ktp->refresh();
        $this->assertSame(1, $employee->documents()->where('document_type', 'ktp')->count());
        $this->assertNotSame($oldPath, $ktp->file_path);
        Storage::disk('private')->assertMissing($oldPath);
        Storage::disk('private')->assertExists($ktp->file_path);

        foreach ([$educationOne, $educationTwo] as $education) {
            $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
                'document_type' => 'ijazah',
                'employee_education_id' => $education->id,
                'file' => UploadedFile::fake()->create("ijazah-{$education->id}.pdf", 10, 'application/pdf'),
            ])->assertRedirect();
        }

        $this->assertSame(2, $employee->documents()->where('document_type', 'ijazah')->count());
        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'employee_education_id' => $educationOne->id,
            'document_slot' => "education:{$educationOne->id}",
        ]);
        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'employee_education_id' => $educationTwo->id,
            'document_slot' => "education:{$educationTwo->id}",
        ]);

        foreach ([$certificationOne, $certificationTwo] as $certification) {
            $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
                'document_type' => 'sertifikat',
                'employee_certification_id' => $certification->id,
                'file' => UploadedFile::fake()->create("sertifikat-{$certification->id}.pdf", 10, 'application/pdf'),
            ])->assertRedirect();
        }

        $this->assertSame(2, $employee->documents()->where('document_type', 'sertifikat')->count());

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ijazah',
            'employee_education_id' => $otherEducation->id,
            'file' => UploadedFile::fake()->create('ijazah-lain.pdf', 10, 'application/pdf'),
        ])->assertNotFound();
        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'sertifikat',
            'employee_certification_id' => $otherCertification->id,
            'file' => UploadedFile::fake()->create('sertifikat-lain.pdf', 10, 'application/pdf'),
        ])->assertNotFound();
    }

    public function test_deleting_education_and_certification_cleans_their_private_documents(): void
    {
        [$user, $employee] = $this->employeeUser();
        $education = $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Uji',
            'is_highest' => true,
        ]);
        $certification = $employee->certifications()->create(['name' => 'Sertifikasi Uji']);
        $educationDocument = $this->putDocument($employee, 'ijazah', "education:{$education->id}", $education->id);
        $certificationDocument = $this->putDocument($employee, 'sertifikat', "certification:{$certification->id}", null, $certification->id);

        $this->actingAs($user)
            ->delete(route('pegawai.profile.educations.destroy', $education, absolute: false))
            ->assertRedirect();
        $this->actingAs($user)
            ->delete(route('pegawai.profile.certifications.destroy', $certification, absolute: false))
            ->assertRedirect();

        $this->assertDatabaseMissing('employee_documents', ['id' => $educationDocument->id]);
        $this->assertDatabaseMissing('employee_documents', ['id' => $certificationDocument->id]);
        Storage::disk('private')->assertMissing($educationDocument->file_path);
        Storage::disk('private')->assertMissing($certificationDocument->file_path);
    }

    public function test_submission_checklist_enforces_general_highest_education_and_conditional_documents(): void
    {
        [, $employee] = $this->employeeUser();
        $education = $this->completeProfile($employee);
        $service = app(EmployeeProfileSubmissionService::class);

        $initial = $service->inspect($employee->fresh());
        $this->assertFalse($initial['can_submit']);
        $this->assertEqualsCanonicalizing(
            ['Kartu Tanda Penduduk', 'Kartu Keluarga', 'Buku/Bukti Rekening', 'Ijazah'],
            $initial['missing_documents'],
        );

        foreach (['ktp', 'kk', 'buku_rekening'] as $type) {
            $this->putDocument($employee, $type);
        }
        $this->putDocument($employee, 'ijazah', "education:{$education->id}", $education->id);

        $complete = $service->inspect($employee->fresh());
        $this->assertTrue($complete['can_submit']);
        $this->assertSame([], $complete['missing_data']);
        $this->assertSame([], $complete['missing_documents']);
        $this->assertFalse($complete['education_documents'][0]['transkrip']['required']);

        $employee->administrativeDetail->update([
            'tax_status' => 'registered',
            'tax_identification_number' => '0011223344556677',
            'nik_used_as_tax_id' => false,
            'bpjs_health_status' => 'active',
            'bpjs_health_number' => '001122334466',
            'bpjs_employment_status' => 'active',
            'bpjs_employment_number' => '001122334477',
        ]);

        $conditional = $service->inspect($employee->fresh());
        $this->assertEqualsCanonicalizing(
            ['Dokumen Pajak', 'Kartu BPJS Kesehatan', 'Kartu BPJS Ketenagakerjaan'],
            $conditional['missing_documents'],
        );

        foreach (['dokumen_pajak', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan'] as $type) {
            $path = "employee-documents/{$employee->id}/missing-{$type}.pdf";
            $employee->documents()->create([
                'document_type' => $type,
                'document_slot' => 'primary',
                'file_path' => $path,
                'original_name' => "{$type}.pdf",
                'mime_type' => 'application/pdf',
                'status' => 'pending',
            ]);
        }

        $metadataOnly = $service->inspect($employee->fresh());
        $this->assertFalse($metadataOnly['can_submit']);
        $this->assertCount(3, $metadataOnly['missing_documents']);

        foreach ($employee->documents()->whereIn('document_type', ['dokumen_pajak', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan'])->get() as $document) {
            Storage::disk('private')->put($document->file_path, '%PDF-1.4 test');
        }
        $this->assertTrue($service->inspect($employee->fresh())['can_submit']);

        $employee->administrativeDetail->update(['nik_used_as_tax_id' => true]);
        $employee->documents()->where('document_type', 'dokumen_pajak')->delete();
        $nikAsTaxId = $service->inspect($employee->fresh());
        $this->assertFalse($nikAsTaxId['main_documents']['dokumen_pajak']['required']);
        $this->assertTrue($nikAsTaxId['can_submit']);
    }

    public function test_single_highest_education_can_temporarily_use_available_legacy_ijazah(): void
    {
        [, $employee] = $this->employeeUser();
        $this->completeProfile($employee);
        foreach (['ktp', 'kk', 'buku_rekening', 'ijazah'] as $type) {
            $this->putDocument($employee, $type);
        }

        $checklist = app(EmployeeProfileSubmissionService::class)->inspect($employee->fresh());

        $this->assertTrue($checklist['can_submit']);
        $this->assertTrue($checklist['education_documents'][0]['ijazah']['legacy_fallback']);
        $this->assertNotEmpty($checklist['warnings']);

        $employee->educations()->create([
            'education_level' => 'magister',
            'institution_name' => 'Universitas Kedua',
        ]);
        $this->assertFalse(app(EmployeeProfileSubmissionService::class)->inspect($employee->fresh())['can_submit']);
    }

    public function test_submit_requires_declaration_complete_data_and_physical_documents_without_changing_official_status(): void
    {
        [$user, $employee] = $this->employeeUser(['employee_number' => null]);

        $this->actingAs($user)->post(route('pegawai.profile.submit', absolute: false), [
            'declaration' => '1',
        ])->assertRedirect(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertSessionHas('error');
        $this->assertSame(Employee::PROFILE_REVIEW_DRAFT, $employee->fresh()->profile_review_status);

        $education = $this->completeProfile($employee);
        foreach (['ktp', 'kk', 'buku_rekening'] as $type) {
            $this->putDocument($employee, $type);
        }
        $this->putDocument($employee, 'ijazah', "education:{$education->id}", $education->id);

        $this->actingAs($user)->post(route('pegawai.profile.submit', absolute: false), [])
            ->assertSessionHasErrors('declaration');

        $originalInstitution = $employee->institution_id;
        $originalPosition = $employee->position_id;
        $this->actingAs($user)->post(route('pegawai.profile.submit', absolute: false), [
            'declaration' => '1',
        ])->assertRedirect(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertSessionHas('success');

        $employee->refresh();
        $this->assertSame(Employee::PROFILE_REVIEW_SUBMITTED, $employee->profile_review_status);
        $this->assertNotNull($employee->profile_submitted_at);
        $this->assertSame('draft', $employee->verification_status);
        $this->assertNull($employee->employee_number);
        $this->assertSame($originalInstitution, $employee->institution_id);
        $this->assertSame($originalPosition, $employee->position_id);
        $submittedAt = $employee->profile_submitted_at->toISOString();

        $this->actingAs($user)->post(route('pegawai.profile.submit', absolute: false), [
            'declaration' => '1',
        ])->assertRedirect(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertSessionHas('warning');
        $this->assertSame($submittedAt, $employee->fresh()->profile_submitted_at->toISOString());
    }

    public function test_submitted_profile_blocks_all_employee_write_paths_but_remains_readable(): void
    {
        [$user, $employee] = $this->employeeUser();
        $family = $employee->familyMembers()->create(['full_name' => 'Keluarga Lama', 'relationship' => 'parent']);
        $education = $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Lama',
            'is_highest' => true,
        ]);
        $certification = $employee->certifications()->create(['name' => 'Sertifikasi Lama']);
        $employee->administrativeDetail()->create(['bank_name' => 'Bank Lama']);
        $employee->forceFill(['profile_review_status' => Employee::PROFILE_REVIEW_SUBMITTED, 'profile_submitted_at' => now()])->save();

        $this->actingAs($user)
            ->get(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertOk()
            ->assertSeeText('Profil sedang menunggu pemeriksaan HR/Admin.')
            ->assertDontSee('Kirim untuk Verifikasi');

        $this->actingAs($user)->put(route('pegawai.profile.wizard.identification.update', absolute: false), [
            'full_name' => 'Nama Baru',
            'wizard_action' => 'stay',
        ])->assertSessionHas('error');
        $this->actingAs($user)->post(route('pegawai.profile.family-members.store', absolute: false), [
            'full_name' => 'Keluarga Baru',
            'relationship' => 'child',
        ])->assertSessionHas('error');
        $this->actingAs($user)->post(route('pegawai.profile.educations.store', absolute: false), [
            'education_level' => 'magister',
            'institution_name' => 'Universitas Baru',
        ])->assertSessionHas('error');
        $this->actingAs($user)->post(route('pegawai.profile.certifications.store', absolute: false), [
            'name' => 'Sertifikasi Baru',
        ])->assertSessionHas('error');
        $this->actingAs($user)->put(route('pegawai.profile.administrative-details.update', absolute: false), [
            'bank_name' => 'Bank Baru',
        ])->assertSessionHas('error');
        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('ktp.pdf', 10, 'application/pdf'),
        ])->assertSessionHas('error');
        $this->actingAs($user)->delete(route('pegawai.profile.family-members.destroy', $family, absolute: false))->assertSessionHas('error');
        $this->actingAs($user)->delete(route('pegawai.profile.educations.destroy', $education, absolute: false))->assertSessionHas('error');
        $this->actingAs($user)->delete(route('pegawai.profile.certifications.destroy', $certification, absolute: false))->assertSessionHas('error');

        $this->assertNotSame('Nama Baru', $employee->fresh()->full_name);
        $this->assertSame('Bank Lama', $employee->administrativeDetail()->first()->bank_name);
        $this->assertSame(1, $employee->familyMembers()->count());
        $this->assertSame(1, $employee->educations()->count());
        $this->assertSame(1, $employee->certifications()->count());
        $this->assertSame(0, $employee->documents()->count());
    }

    public function test_document_upload_rejects_unsafe_or_oversized_files_and_hr_document_types(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('payload.html', 10, 'text/html'),
        ])->assertSessionHasErrors('file');
        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('besar.pdf', 5121, 'application/pdf'),
        ])->assertSessionHasErrors('file');
        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'sk_pengangkatan',
            'file' => UploadedFile::fake()->create('sk.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('document_type');

        $this->assertSame(0, $employee->documents()->count());
    }

    public function test_review_masks_sensitive_data_and_never_exposes_private_paths(): void
    {
        [$user, $employee] = $this->employeeUser([
            'nik' => '3201010101011234',
            'family_card_number' => '3201010101015678',
        ]);
        $education = $this->completeProfile($employee);
        $employee->update([
            'nik' => '3201010101011234',
            'family_card_number' => '3201010101015678',
        ]);
        $employee->familyMembers()->create([
            'full_name' => 'Pasangan Uji',
            'relationship' => 'spouse',
            'nik' => '3201010101019012',
        ]);
        $employee->certifications()->create([
            'name' => 'Sertifikasi Rahasia',
            'certificate_number' => 'SERTIFIKAT-RAHASIA-5678',
        ]);
        $education->update(['certificate_number' => 'IJAZAH-RAHASIA-1234']);
        $document = $this->putDocument($employee, 'ktp');

        $response = $this->actingAs($user)
            ->get(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertOk()
            ->assertSee('************1234')
            ->assertSee('************5678')
            ->assertDontSee($document->file_path);

        foreach (['3201010101011234', '3201010101015678', '3201010101019012', 'IJAZAH-RAHASIA-1234', 'SERTIFIKAT-RAHASIA-5678'] as $secret) {
            $response->assertDontSee($secret);
        }
    }

    /** @param array<string, mixed> $overrides */
    private function employeeUser(array $overrides = [], string $email = 'profile.submit@yapista.test'): array
    {
        $institution = Institution::create(['name' => 'Unit '.uniqid(), 'level' => 'SMK', 'status' => 'active']);
        $position = Position::create(['institution_id' => $institution->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);
        $user = User::factory()->create(['email' => $email, 'role' => 'pegawai', 'status' => 'active']);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Submission',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $overrides));

        return [$user, $employee];
    }

    private function completeProfile(Employee $employee): EmployeeEducation
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
        $education = $employee->educations()->firstOrCreate([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Uji',
        ], ['is_highest' => true]);
        $education->update(['is_highest' => true]);
        $employee->administrativeDetail()->updateOrCreate([], [
            'bank_name' => 'Bank Uji',
            'bank_account_number' => '001122334455',
            'bank_account_holder' => 'Pegawai Lengkap',
            'tax_status' => 'not_registered',
            'bpjs_health_status' => 'not_registered',
            'bpjs_employment_status' => 'not_registered',
        ]);

        return $education;
    }

    private function putDocument(
        Employee $employee,
        string $type,
        string $slot = 'primary',
        ?int $educationId = null,
        ?int $certificationId = null,
    ): EmployeeDocument {
        $path = "employee-documents/{$employee->id}/{$type}-".str_replace(':', '-', $slot).'-'.uniqid().'.pdf';
        Storage::disk('private')->put($path, '%PDF-1.4 test');

        return $employee->documents()->create([
            'employee_education_id' => $educationId,
            'employee_certification_id' => $certificationId,
            'document_type' => $type,
            'document_slot' => $slot,
            'file_path' => $path,
            'original_name' => "{$type}.pdf",
            'mime_type' => 'application/pdf',
            'file_size' => 13,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);
    }
}
