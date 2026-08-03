<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('public');
    }

    public function test_super_admin_and_hr_admin_can_preview_employee_document(): void
    {
        [, $employee] = $this->employeeUser();
        $document = $this->privateDocument($employee);

        foreach (['super_admin', 'hr_admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)
                ->get(route('employee-documents.view', $document))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf')
                ->assertHeader('cache-control', 'max-age=0, no-store, private')
                ->assertHeader('x-content-type-options', 'nosniff')
                ->assertHeader('x-frame-options', 'SAMEORIGIN');
        }
    }

    public function test_employee_can_preview_and_download_own_document(): void
    {
        [$user, $employee] = $this->employeeUser();
        $document = $this->privateDocument($employee);

        $this->actingAs($user)
            ->get(route('employee-documents.view', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('employee-documents.download', $document))
            ->assertOk()
            ->assertDownload('ktp.pdf')
            ->assertHeader('cache-control', 'max-age=0, no-store, private');
    }

    public function test_super_admin_can_download_employee_document(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        [, $employee] = $this->employeeUser();
        $document = $this->privateDocument($employee);

        $this->actingAs($admin)
            ->get(route('employee-documents.download', $document))
            ->assertOk()
            ->assertDownload('ktp.pdf');
    }

    public function test_employee_can_not_preview_or_download_another_employee_document(): void
    {
        [$user] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser(email: 'other@yapista.test');
        $document = $this->privateDocument($otherEmployee);

        $this->actingAs($user)
            ->get(route('employee-documents.view', $document))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('employee-documents.download', $document))
            ->assertForbidden();
    }

    public function test_panitia_can_not_preview_or_download_employee_document(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        [, $employee] = $this->employeeUser();
        $document = $this->privateDocument($employee);

        $this->actingAs($panitia)
            ->get(route('employee-documents.view', $document))
            ->assertForbidden();

        $this->actingAs($panitia)
            ->get(route('employee-documents.download', $document))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_document_access(): void
    {
        [, $employee] = $this->employeeUser();
        $document = $this->privateDocument($employee);

        $this->get(route('employee-documents.view', $document))->assertRedirect(route('login'));
        $this->get(route('employee-documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_new_upload_is_stored_only_on_private_disk(): void
    {
        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)
            ->post(route('pegawai.documents.store'), [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('pegawai.documents.index'));

        $document = EmployeeDocument::where('employee_id', $employee->id)->firstOrFail();

        $this->assertStringStartsWith("employee-documents/{$employee->id}/", $document->file_path);
        Storage::disk('private')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }

    public function test_replace_stores_new_private_file_before_removing_legacy_file(): void
    {
        [$user, $employee] = $this->employeeUser();
        $oldPath = "employees/documents/legacy-{$employee->id}.pdf";
        Storage::disk('public')->put($oldPath, 'legacy document');
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => $oldPath,
            'original_name' => 'ktp-lama.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'rejected',
            'note' => 'File kurang jelas.',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('pegawai.documents.store'), [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->image('ktp-baru.jpg'),
            ])
            ->assertRedirect(route('pegawai.documents.index'));

        $document->refresh();
        $this->assertNotSame($oldPath, $document->file_path);
        $this->assertSame('pending', $document->status);
        $this->assertNull($document->note);
        Storage::disk('private')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_delete_removes_metadata_and_private_or_legacy_copies(): void
    {
        [$user, $employee] = $this->employeeUser();
        $path = "employee-documents/{$employee->id}/ktp.pdf";
        Storage::disk('private')->put($path, 'private copy');
        Storage::disk('public')->put($path, 'legacy copy');
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => $path,
            'original_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('pegawai.documents.destroy', $document))
            ->assertRedirect(route('pegawai.documents.index'));

        $this->assertDatabaseMissing('employee_documents', ['id' => $document->id]);
        Storage::disk('private')->assertMissing($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_missing_document_file_returns_404_instead_of_500(): void
    {
        [$user, $employee] = $this->employeeUser();
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => "employee-documents/{$employee->id}/missing.pdf",
            'original_name' => 'missing.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)->get(route('employee-documents.view', $document))->assertNotFound();
        $this->actingAs($user)->get(route('employee-documents.download', $document))->assertNotFound();
    }

    public function test_authorized_access_can_preview_legacy_public_document_through_controller(): void
    {
        [$user, $employee] = $this->employeeUser();
        $path = "employees/documents/legacy-preview-{$employee->id}.pdf";
        Storage::disk('public')->put($path, '%PDF-1.4 legacy document');
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => $path,
            'original_name' => 'ktp-legacy.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('employee-documents.view', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('cache-control', 'max-age=0, no-store, private');

        Storage::disk('private')->assertMissing($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_migration_command_dry_run_does_not_move_or_delete_public_file(): void
    {
        [, $employee] = $this->employeeUser();
        $path = "employees/documents/legacy-{$employee->id}.pdf";
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => $path,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);
        Storage::disk('public')->put($document->file_path, 'legacy document');

        $exitCode = Artisan::call('employee-documents:migrate-private', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Would move: 1', $output);
        $this->assertStringContainsString('Moved: 0', $output);
        Storage::disk('public')->assertExists($path);
        Storage::disk('private')->assertMissing($path);
    }

    public function test_migration_command_moves_public_document_but_not_employee_photo(): void
    {
        [, $employee] = $this->employeeUser();
        $path = "employees/documents/legacy-{$employee->id}.pdf";
        $photoPath = "employees/photos/profile-{$employee->id}.jpg";
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => $path,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);
        Storage::disk('public')->put($path, 'legacy document');
        Storage::disk('public')->put($photoPath, 'profile photo');

        $exitCode = Artisan::call('employee-documents:migrate-private');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Moved: 1', Artisan::output());
        Storage::disk('private')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($photoPath);
    }

    /**
     * @return array{User, Employee}
     */
    private function employeeUser(string $email = 'pegawai@yapista.test'): array
    {
        $institution = Institution::create([
            'name' => 'Unit '.uniqid(),
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Jabatan '.uniqid(),
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'email' => $email,
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai Dokumen',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ]);

        return [$user, $employee];
    }

    private function privateDocument(Employee $employee): EmployeeDocument
    {
        $path = "employee-documents/{$employee->id}/ktp.pdf";
        Storage::disk('private')->put($path, '%PDF-1.4 private document');

        return EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => $path,
            'original_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => Storage::disk('private')->size($path),
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);
    }
}
