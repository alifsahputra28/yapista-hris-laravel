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

class EmployeeProfileDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_update_profile_photo_upload_replace_delete_document_and_submit(): void
    {
        Storage::fake('public');

        [$user, $employee] = $this->employeeUser();

        $this->actingAs($user)
            ->get('/pegawai/profile')
            ->assertOk()
            ->assertSee($employee->full_name);

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => 'Ahmad Fauzi Update',
                'nik' => '3201010101010001',
                'gender' => 'male',
                'birth_place' => 'Bekasi',
                'birth_date' => '1990-01-01',
                'phone' => '081234567890',
                'address' => 'Jl. Pendidikan',
                'institution_id' => 999,
                'verification_status' => 'verified',
                'photo' => UploadedFile::fake()->image('profile.jpg'),
            ])
            ->assertRedirect(route('pegawai.profile.show', absolute: false));

        $employee->refresh();
        $this->assertSame('Ahmad Fauzi Update', $employee->full_name);
        $this->assertSame('draft', $employee->verification_status);
        $this->assertNotSame(999, $employee->institution_id);
        $this->assertNotNull($employee->photo);
        Storage::disk('public')->assertExists($employee->photo);

        $this->actingAs($user)
            ->post('/pegawai/profile/submit')
            ->assertRedirect(route('pegawai.profile.show', absolute: false));

        $this->assertSame('draft', $employee->refresh()->verification_status);

        $this->actingAs($user)
            ->post('/pegawai/documents', [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('pegawai.documents.index', absolute: false));

        $document = EmployeeDocument::where('employee_id', $employee->id)->where('document_type', 'ktp')->firstOrFail();
        $firstPath = $document->file_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user)
            ->post('/pegawai/documents', [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->image('ktp-baru.jpg'),
            ])
            ->assertRedirect(route('pegawai.documents.index', absolute: false));

        $document->refresh();
        $this->assertSame(1, EmployeeDocument::where('employee_id', $employee->id)->where('document_type', 'ktp')->count());
        $this->assertNotSame($firstPath, $document->file_path);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($document->file_path);

        $this->actingAs($user)
            ->delete("/pegawai/documents/{$document->id}")
            ->assertRedirect(route('pegawai.documents.index', absolute: false));

        $this->assertDatabaseMissing('employee_documents', ['id' => $document->id]);
        Storage::disk('public')->assertMissing($document->file_path);

        $this->actingAs($user)
            ->post('/pegawai/documents', [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->image('ktp-final.jpg'),
            ])
            ->assertRedirect(route('pegawai.documents.index', absolute: false));

        $this->actingAs($user)
            ->post('/pegawai/profile/submit')
            ->assertRedirect(route('pegawai.profile.show', absolute: false));

        $this->assertSame('submitted', $employee->refresh()->verification_status);
        $this->assertNull($employee->verification_note);
    }

    public function test_employee_can_not_edit_or_upload_documents_after_submission(): void
    {
        Storage::fake('public');

        [$user, $employee] = $this->employeeUser([
            'verification_status' => 'submitted',
            'nik' => '3201010101010001',
            'phone' => '081234567890',
            'address' => 'Jl. Pendidikan',
            'photo' => 'employees/photos/existing.jpg',
        ]);

        $this->actingAs($user)
            ->get('/pegawai/profile/edit')
            ->assertRedirect(route('pegawai.profile.show', absolute: false));

        $this->actingAs($user)
            ->put('/pegawai/profile', [
                'full_name' => 'Tidak Boleh',
                'nik' => '3201010101010001',
                'phone' => '081234567890',
                'address' => 'Jl. Pendidikan',
            ])
            ->assertRedirect(route('pegawai.profile.show', absolute: false));

        $this->assertNotSame('Tidak Boleh', $employee->refresh()->full_name);

        $this->actingAs($user)
            ->post('/pegawai/documents', [
                'document_type' => 'ktp',
                'file' => UploadedFile::fake()->image('ktp.jpg'),
            ])
            ->assertRedirect(route('pegawai.documents.index', absolute: false));

        $this->assertDatabaseCount('employee_documents', 0);
    }

    public function test_employee_can_not_delete_another_employee_document(): void
    {
        Storage::fake('public');

        [$user] = $this->employeeUser();
        [, $otherEmployee] = $this->employeeUser(email: 'other@yapista.test');
        $document = EmployeeDocument::create([
            'employee_id' => $otherEmployee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/other.pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete("/pegawai/documents/{$document->id}")
            ->assertForbidden();
    }

    public function test_admin_can_see_employee_documents_on_employee_detail(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        [, $employee] = $this->employeeUser();
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertSee('KTP')
            ->assertSee('ktp.pdf');
    }

    /**
     * @param  array<string, mixed>  $employeeOverrides
     * @return array{User, Employee}
     */
    private function employeeUser(array $employeeOverrides = [], string $email = 'pegawai@yapista.test'): array
    {
        $institution = Institution::create([
            'name' => 'SMK Ibnu Sina '.uniqid(),
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
            'email' => $email,
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Ahmad Fauzi',
            'email' => $email,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $employeeOverrides));

        return [$user, $employee];
    }
}
