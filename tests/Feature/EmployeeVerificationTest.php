<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_verification_queue_and_approve_submitted_employee_with_valid_ktp(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $existing = $this->employee([
            'employee_number' => '777.0526.0001',
            'verification_status' => 'verified',
        ]);
        $employee = $this->employee([
            'full_name' => 'Siti Aminah',
            'email' => 'siti@yapista.test',
            'join_date' => '2026-05-10',
            'foundation_registry_number' => 25,
            'verification_status' => 'submitted',
        ]);
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'status' => 'valid',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/verifications')
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertDontSee($existing->full_name);

        $this->actingAs($admin)
            ->get("/verifications/{$employee->id}")
            ->assertOk()
            ->assertSee('ktp.pdf');

        $this->actingAs($admin)
            ->post(route('verifications.approve', $employee, absolute: false))
            ->assertRedirect(route('verifications.show', $employee, absolute: false));

        $employee->refresh();

        $this->assertSame('verified', $employee->verification_status);
        $this->assertSame('777.0526.0025', $employee->employee_number);
        $this->assertSame($admin->id, $employee->verified_by);
        $this->assertNotNull($employee->verified_at);
    }

    public function test_admin_approval_replaces_old_employee_number_format_when_submitted(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $employee = $this->employee([
            'employee_number' => 'YAPISTA-2026-0005',
            'join_date' => '2026-05-10',
            'foundation_registry_number' => 25,
            'verification_status' => 'submitted',
        ]);
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'status' => 'valid',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('verifications.approve', $employee, absolute: false))
            ->assertRedirect(route('verifications.show', $employee, absolute: false));

        $employee->refresh();

        $this->assertSame('verified', $employee->verification_status);
        $this->assertSame('777.0526.0025', $employee->employee_number);
    }

    public function test_admin_can_not_approve_without_valid_ktp(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        $employee = $this->employee([
            'verification_status' => 'submitted',
        ]);
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('verifications.approve', $employee, absolute: false))
            ->assertRedirect(route('verifications.show', $employee, absolute: false))
            ->assertSessionHas('error', 'Dokumen KTP harus berstatus valid sebelum pegawai diverifikasi.');

        $this->assertSame('submitted', $employee->refresh()->verification_status);
        $this->assertNull($employee->employee_number);
    }

    public function test_admin_can_not_approve_without_foundation_registry_number(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        $employee = $this->employee([
            'foundation_registry_number' => null,
            'verification_status' => 'submitted',
        ]);
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'status' => 'valid',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('verifications.approve', $employee, absolute: false))
            ->assertRedirect(route('verifications.show', $employee, absolute: false))
            ->assertSessionHas('error', 'Nomor urut buku yayasan belum diisi.');

        $this->assertSame('submitted', $employee->refresh()->verification_status);
        $this->assertNull($employee->employee_number);
    }

    public function test_admin_can_not_approve_without_join_date_or_foundation_registry_number(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        $employee = $this->employee([
            'join_date' => null,
            'foundation_registry_number' => null,
            'verification_status' => 'submitted',
        ]);
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'status' => 'valid',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('verifications.approve', $employee, absolute: false))
            ->assertRedirect(route('verifications.show', $employee, absolute: false))
            ->assertSessionHas('error', 'Tanggal masuk pegawai belum diisi.');

        $this->assertSame('submitted', $employee->refresh()->verification_status);
        $this->assertNull($employee->employee_number);
    }

    public function test_admin_can_not_approve_when_generated_employee_number_already_exists(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $this->employee([
            'employee_number' => '777.0526.0025',
            'verification_status' => 'verified',
        ]);
        $employee = $this->employee([
            'join_date' => '2026-05-10',
            'foundation_registry_number' => 25,
            'verification_status' => 'submitted',
        ]);
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'status' => 'valid',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('verifications.approve', $employee, absolute: false))
            ->assertRedirect(route('verifications.show', $employee, absolute: false))
            ->assertSessionHas('error', 'Nomor pegawai sudah digunakan. Periksa kembali nomor urut buku yayasan.');

        $this->assertSame('submitted', $employee->refresh()->verification_status);
        $this->assertNull($employee->employee_number);
    }

    public function test_admin_can_reject_submitted_employee_with_note(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        $employee = $this->employee([
            'verification_status' => 'submitted',
        ]);

        $this->actingAs($admin)
            ->post(route('verifications.reject', $employee, absolute: false), [
                'verification_note' => 'NIK belum sesuai dokumen.',
            ])
            ->assertRedirect(route('verifications.index', absolute: false));

        $employee->refresh();

        $this->assertSame('rejected', $employee->verification_status);
        $this->assertSame('NIK belum sesuai dokumen.', $employee->verification_note);
        $this->assertSame($admin->id, $employee->verified_by);
        $this->assertNull($employee->verified_at);
        $this->assertTrue($employee->canEditProfile());
    }

    public function test_document_rejection_requires_note_and_returns_employee_for_revision(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $employee = $this->employee([
            'verification_status' => 'submitted',
        ]);
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employees/documents/ktp.pdf',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('employee-documents.update-status', $document, absolute: false), [
                'status' => 'rejected',
            ])
            ->assertSessionHasErrors('note');

        $this->actingAs($admin)
            ->patch(route('employee-documents.update-status', $document, absolute: false), [
                'status' => 'rejected',
                'note' => 'Foto KTP buram.',
            ])
            ->assertRedirect(route('verifications.show', $employee, absolute: false));

        $this->assertSame('rejected', $document->refresh()->status);
        $this->assertSame('Foto KTP buram.', $document->note);
        $this->assertSame('rejected', $employee->refresh()->verification_status);
        $this->assertStringContainsString('Dokumen KTP ditolak', $employee->verification_note);
    }

    public function test_non_admin_roles_can_not_access_verification_routes(): void
    {
        foreach (['panitia', 'pegawai'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get('/verifications')
                ->assertForbidden();
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function employee(array $overrides = []): Employee
    {
        $institution = Institution::create([
            'name' => 'Unit '.uniqid(),
            'level' => 'SMK',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Guru '.uniqid(),
            'type' => 'fungsional',
            'status' => 'active',
        ]);

        return Employee::create(array_merge([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Ahmad Fauzi',
            'email' => 'ahmad'.uniqid().'@yapista.test',
            'nik' => '320101010101'.random_int(1000, 9999),
            'phone' => '081234567890',
            'address' => 'Jl. Pendidikan',
            'photo' => 'employees/photos/profile.jpg',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'join_date' => '2026-05-10',
            'foundation_registry_number' => random_int(1, 9999),
            'verification_status' => 'submitted',
        ], $overrides));
    }
}
