<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Database\Seeders\EmployeeQrTokenSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class EmployeeOnboardingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_login_ready_draft_employees_with_empty_profiles(): void
    {
        $this->seedOnboarding();

        $this->assertSame(12, Employee::count());
        $this->assertSame(12, User::where('role', 'pegawai')->count());
        $this->assertSame(12, Employee::whereNotNull('user_id')->count());
        $this->assertSame(12, Employee::where('verification_status', 'draft')->count());
        $this->assertSame(12, Employee::all()->filter->hasValidEmployeeNumber()->count());
        $this->assertSame(0, DB::table('employees')->select('employee_number')->groupBy('employee_number')->havingRaw('count(*) > 1')->count());

        $employee = Employee::where('employee_number', '7770923822')->firstOrFail();
        $user = $employee->user()->firstOrFail();
        $this->assertSame('pegawai', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('password', $user->password));

        foreach ([
            'email', 'nik', 'family_card_number', 'gender', 'birth_place', 'birth_date',
            'religion', 'marital_status', 'nationality', 'blood_type', 'phone',
            'whatsapp_number', 'identity_address', 'address', 'domicile_same_as_identity',
            'domicile_province', 'domicile_city', 'domicile_district', 'domicile_village',
            'domicile_postal_code', 'emergency_contact_name', 'emergency_contact_relationship',
            'emergency_contact_phone', 'emergency_contact_address', 'photo',
        ] as $field) {
            $this->assertNull($employee->getAttribute($field), "Field {$field} seharusnya null.");
        }

        $this->assertFalse($employee->familyMembers()->exists());
        $this->assertFalse($employee->educations()->exists());
        $this->assertFalse($employee->certifications()->exists());
        $this->assertFalse($employee->administrativeDetail()->exists());
        $this->assertFalse($employee->documents()->exists());
        $this->assertFalse($employee->qrTokens()->exists());
        $this->assertFalse($employee->eventParticipants()->exists());
        $this->assertFalse($employee->eventAttendances()->exists());
        $this->assertNull($employee->getRawOriginal('nup'));
        $this->assertNull($employee->getRawOriginal('foundation_registry_number'));
    }

    public function test_rerun_preserves_password_profile_status_and_related_records(): void
    {
        $this->seedOnboarding();

        $employee = Employee::where('employee_number', '7770923824')->firstOrFail();
        $user = $employee->user()->firstOrFail();
        $admin = User::where('email', 'admin@yapista.test')->firstOrFail();
        $customPassword = Hash::make('do-not-reset-me');
        $user->update(['password' => $customPassword]);
        $employee->forceFill([
            'email' => 'personal.budi@yapista.test',
            'nik' => '2171011603880003',
            'family_card_number' => '2171011603889999',
            'phone' => '081277709003',
            'address' => 'Alamat yang sudah dilengkapi',
            'photo' => 'employees/photos/budi.jpg',
            'verification_status' => 'verified',
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'profile_review_status' => Employee::PROFILE_REVIEW_APPROVED,
            'profile_reviewed_by' => $admin->id,
            'profile_reviewed_at' => now(),
        ])->save();
        $family = $employee->familyMembers()->create(['full_name' => 'Keluarga Budi', 'relationship' => 'spouse']);
        $education = $employee->educations()->create(['education_level' => 'sarjana', 'institution_name' => 'Universitas Uji', 'is_highest' => true]);
        $administration = $employee->administrativeDetail()->create(['bank_name' => 'Bank Uji']);
        $document = EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'document_slot' => 'primary',
            'file_path' => 'employee-documents/'.$employee->id.'/existing.pdf',
            'original_name' => 'existing.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        $this->seed(EmployeeSeeder::class);

        $employee->refresh();
        $this->assertSame($user->id, $employee->user_id);
        $this->assertTrue(Hash::check('do-not-reset-me', $user->fresh()->password));
        $this->assertSame('personal.budi@yapista.test', $employee->email);
        $this->assertSame('2171011603880003', $employee->nik);
        $this->assertSame('2171011603889999', $employee->family_card_number);
        $this->assertSame('081277709003', $employee->phone);
        $this->assertSame('Alamat yang sudah dilengkapi', $employee->address);
        $this->assertSame('employees/photos/budi.jpg', $employee->photo);
        $this->assertSame('verified', $employee->verification_status);
        $this->assertSame($admin->id, $employee->verified_by);
        $this->assertNotNull($employee->verified_at);
        $this->assertSame(Employee::PROFILE_REVIEW_APPROVED, $employee->profile_review_status);
        $this->assertDatabaseHas('employee_family_members', ['id' => $family->id]);
        $this->assertDatabaseHas('employee_educations', ['id' => $education->id]);
        $this->assertDatabaseHas('employee_administrative_details', ['id' => $administration->id]);
        $this->assertDatabaseHas('employee_documents', ['id' => $document->id]);
    }

    public function test_data_validation_rejects_invalid_rows_before_any_employee_is_written(): void
    {
        $this->seedMasterData();
        $seeder = app(EmployeeSeeder::class);
        $valid = $this->validRow();
        $cases = [
            'employee_number' => [array_merge($valid, ['employee_number' => '123']), 'employee_number harus tepat 10 digit'],
            'login_email' => [array_merge($valid, ['login_email' => 'bukan-email']), 'login_email tidak valid'],
            'institution' => [array_merge($valid, ['institution_name' => 'Unit Tidak Ada']), 'unit kerja Unit Tidak Ada tidak ditemukan'],
            'position' => [array_merge($valid, ['position_name' => 'Jabatan Tidak Ada']), 'jabatan Jabatan Tidak Ada'],
            'employee_type' => [array_merge($valid, ['employee_type' => 'tidak_valid']), 'employee_type tidak_valid tidak valid'],
            'employment_status' => [array_merge($valid, ['employment_status' => 'tidak_valid']), 'employment_status tidak_valid tidak valid'],
            'join_date' => [array_merge($valid, ['join_date' => '2026-02-31']), 'join_date harus memakai format'],
        ];

        foreach ($cases as [$row, $message]) {
            try {
                $seeder->seedRows([$row]);
                $this->fail("Validasi {$message} seharusnya melempar exception.");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }

        foreach ([
            [[$valid, array_merge($valid, ['login_email' => 'other@yapista.test'])], 'employee_number', 'duplikat'],
            [[$valid, array_merge($valid, ['employee_number' => '1000000002'])], 'login_email', 'duplikat'],
        ] as [$rows, $field, $message]) {
            try {
                $seeder->seedRows($rows);
                $this->fail("Duplikasi {$field} seharusnya ditolak.");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString($field, $exception->getMessage());
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }

        $this->assertSame(0, Employee::count());
        $this->assertSame(0, User::where('role', 'pegawai')->count());
    }

    public function test_conflicting_account_links_are_rejected_without_moving_accounts(): void
    {
        $this->seedMasterData();
        $seeder = app(EmployeeSeeder::class);
        $seeder->seedRows([$this->validRow()]);

        $conflict = $this->validRow() + [];
        $conflict['employee_number'] = '1000000002';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('login_email sudah terhubung ke pegawai lain');
        $seeder->seedRows([$conflict]);
    }

    public function test_seeded_draft_employee_can_open_and_update_the_profile_wizard(): void
    {
        $this->seedOnboarding();
        $employee = Employee::where('employee_number', '7770923822')->firstOrFail();
        $user = $employee->user()->firstOrFail();

        $this->actingAs($user)
            ->get(route('pegawai.profile.wizard.show', 'identification', absolute: false))
            ->assertOk()
            ->assertSee('Identitas Pribadi');

        $this->actingAs($user)
            ->put(route('pegawai.profile.wizard.identification.update', absolute: false), [
                'full_name' => 'Ahmad Fauzi Diperbarui',
                'nik' => '2171011201900001',
                'wizard_action' => 'stay',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pegawai.profile.wizard.show', 'identification', absolute: false));

        $this->assertSame('Ahmad Fauzi Diperbarui', $employee->fresh()->full_name);
        $this->assertSame('draft', $employee->fresh()->verification_status);
    }

    public function test_qr_seeder_skips_drafts_and_is_idempotent_for_verified_employees(): void
    {
        $this->seedOnboarding();

        $this->seed(EmployeeQrTokenSeeder::class);
        $this->assertSame(0, DB::table('employee_qr_tokens')->count());

        $employee = Employee::where('employee_number', '7770923822')->firstOrFail();
        $employee->update(['verification_status' => 'verified']);
        $this->seed(EmployeeQrTokenSeeder::class);

        $token = $employee->qrTokens()->where('is_active', true)->firstOrFail();
        $this->assertSame(64, strlen($token->token_encrypted));
        $this->assertNotSame($employee->employee_number, $token->token_encrypted);
        $this->assertSame(hash('sha256', $token->token_encrypted), $token->token_hash);
        $this->assertNotSame($token->token_encrypted, $token->getRawOriginal('token_encrypted'));
        $this->assertTrue($token->isActive());

        $this->seed(EmployeeQrTokenSeeder::class);
        $this->assertSame(1, $employee->qrTokens()->where('is_active', true)->whereNull('revoked_at')->count());
        $this->assertSame($token->id, $employee->qrTokens()->where('is_active', true)->value('id'));
    }

    private function seedMasterData(): void
    {
        $this->seed([
            UserSeeder::class,
            InstitutionSeeder::class,
            PositionSeeder::class,
        ]);
    }

    private function seedOnboarding(): void
    {
        $this->seedMasterData();
        $this->seed(EmployeeSeeder::class);
    }

    /** @return array<string, mixed> */
    private function validRow(): array
    {
        return [
            'employee_number' => '1000000001',
            'full_name' => 'Pegawai Validasi',
            'login_email' => 'pegawai.validasi@yapista.test',
            'personal_email' => null,
            'institution_name' => 'SMK Ibnu Sina',
            'position_name' => 'Guru',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'join_date' => null,
        ];
    }
}
