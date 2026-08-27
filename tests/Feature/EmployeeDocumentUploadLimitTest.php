<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentUploadLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('public');
    }

    public function test_employee_document_upload_accepts_files_up_to_two_mb_and_rejects_larger_files(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('ktp.pdf', 2047, 'application/pdf'),
        ])->assertRedirect(route('pegawai.documents.index', absolute: false))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'kk',
            'file' => UploadedFile::fake()->create('kk.pdf', 2048, 'application/pdf'),
        ])->assertRedirect(route('pegawai.documents.index', absolute: false))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'buku_rekening',
            'file' => UploadedFile::fake()->create('rekening.pdf', 2049, 'application/pdf'),
        ])->assertSessionHasErrors([
            'file' => 'Ukuran dokumen maksimal 2 MB.',
        ]);

        $this->assertSame(2, $employee->documents()->count());
        $this->assertDatabaseMissing('employee_documents', ['document_type' => 'buku_rekening']);
    }

    public function test_wizard_education_and_certification_documents_use_the_same_two_mb_limit(): void
    {
        [$user, $employee] = $this->employeeUser();
        $education = $employee->educations()->create([
            'education_level' => 'sarjana',
            'institution_name' => 'Universitas Uji',
            'is_highest' => true,
        ]);
        $certification = $employee->certifications()->create(['name' => 'Sertifikasi Uji']);

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ijazah',
            'document_context' => 'wizard',
            'employee_education_id' => $education->id,
            'file' => UploadedFile::fake()->create('ijazah.pdf', 2048, 'application/pdf'),
        ])->assertRedirect(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'sertifikat',
            'document_context' => 'wizard',
            'employee_certification_id' => $certification->id,
            'file' => UploadedFile::fake()->create('sertifikat.pdf', 2048, 'application/pdf'),
        ])->assertRedirect(route('pegawai.profile.wizard.show', 'review', absolute: false))
            ->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'transkrip',
            'document_context' => 'wizard',
            'employee_education_id' => $education->id,
            'file' => UploadedFile::fake()->create('transkrip.pdf', 2049, 'application/pdf'),
        ])->assertSessionHasErrors([
            'file' => 'Ukuran dokumen maksimal 2 MB.',
        ]);

        $this->assertSame(2, $employee->documents()->count());
    }

    public function test_invalid_document_format_is_rejected_below_the_size_limit(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('payload.html', 100, 'text/html'),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, $employee->documents()->count());
    }

    public function test_oversized_replacement_does_not_remove_the_existing_document(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('ktp-lama.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $document = $employee->documents()->where('document_type', 'ktp')->firstOrFail();
        $oldPath = $document->file_path;

        $this->actingAs($user)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('ktp-baru.pdf', 2049, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertSame($oldPath, $document->fresh()->file_path);
        Storage::disk('private')->assertExists($oldPath);
        $this->assertSame(1, EmployeeDocument::where('employee_id', $employee->id)->where('document_type', 'ktp')->count());
    }

    public function test_document_upload_ui_shows_the_standard_limit_and_non_employee_roles_cannot_upload(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)
            ->get(route('pegawai.documents.index', absolute: false))
            ->assertOk()
            ->assertSeeText('Maksimal 2 MB per file.');

        $admin = User::factory()->create(['role' => 'hr_admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('pegawai.documents.store', absolute: false), [
            'document_type' => 'ktp',
            'file' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
    }

    /** @return array{User, Employee} */
    private function employeeUser(): array
    {
        $institution = Institution::create([
            'name' => 'Unit Dokumen '.uniqid(),
            'level' => 'SMK',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Guru',
            'type' => 'fungsional',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'email' => 'document.limit.'.uniqid().'@yapista.test',
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Uji Dokumen',
            'email' => $user->email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ]);

        return [$user, $employee];
    }
}
