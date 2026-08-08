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

    public function test_seeder_distinguishes_existing_and_new_employees_without_faking_profiles(): void
    {
        $this->seedOnboarding();

        $this->assertSame(13, Employee::count());
        $this->assertSame(13, User::where('role', 'pegawai')->count());
        $this->assertSame(13, Employee::whereNotNull('user_id')->count());
        $this->assertSame(12, Employee::where('verification_status', 'verified')->count());
        $this->assertSame(1, Employee::where('verification_status', 'draft')->count());
        $this->assertSame(12, Employee::all()->filter->hasValidEmployeeNumber()->count());
        $this->assertSame(12, DB::table('employee_qr_tokens')->where('is_active', true)->whereNull('revoked_at')->count());
        $this->assertSame(0, DB::table('employees')->select('employee_number')->groupBy('employee_number')->havingRaw('count(*) > 1')->count());

        $employee = Employee::where('employee_number', '7770923822')->firstOrFail();
        $user = $employee->user()->firstOrFail();
        $this->assertSame('pegawai', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertSame('verified', $employee->verification_status);
        $this->assertTrue($employee->isEligibleForIdCard());
        $this->assertNotNull($employee->activeQrToken()->first());
        $this->assertNull($employee->verified_at);
        $this->assertNull($employee->verified_by);

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
        $this->assertTrue($employee->qrTokens()->exists());
        $this->assertFalse($employee->eventParticipants()->exists());
        $this->assertFalse($employee->eventAttendances()->exists());
        $this->assertNull($employee->getRawOriginal('nup'));
        $this->assertNull($employee->getRawOriginal('foundation_registry_number'));

        $newEmployee = Employee::whereHas('user', fn ($query) => $query->where('email', 'pegawai.baru@yapista.test'))->firstOrFail();
        $this->assertNull($newEmployee->employee_number);
        $this->assertSame('draft', $newEmployee->verification_status);
        $this->assertFalse($newEmployee->qrTokens()->exists());
        $this->assertFalse($newEmployee->isEligibleForIdCard());
        $this->assertTrue(Hash::check('password', $newEmployee->user->password));
    }

    public function test_rerun_preserves_password_profile_status_and_related_records(): void
    {
        $this->seedOnboarding();

        $employee = Employee::where('employee_number', '7770923824')->firstOrFail();
        $user = $employee->user()->firstOrFail();
        $qrToken = $employee->activeQrToken()->firstOrFail();
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
        $this->assertSame($qrToken->id, $employee->activeQrToken()->value('id'));
        $this->assertSame(1, $employee->qrTokens()->where('is_active', true)->whereNull('revoked_at')->count());
    }

    public function test_data_validation_rejects_invalid_rows_before_any_employee_is_written(): void
    {
        $this->seedMasterData();
        $seeder = app(EmployeeSeeder::class);
        $valid = $this->validRow();
        $cases = [
            'employee_number' => [array_merge($valid, ['employee_number' => '123']), 'employee_number harus null atau tepat 10 digit'],
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

        $seeder->seedRows([
            array_merge($valid, ['employee_number' => null]),
            array_merge($valid, ['employee_number' => null, 'login_email' => 'pegawai.validasi.dua@yapista.test']),
        ]);
        $this->assertSame(2, Employee::whereNull('employee_number')->where('verification_status', 'draft')->count());
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

    public function test_seeded_new_employee_can_open_and_update_the_profile_wizard(): void
    {
        $this->seedOnboarding();
        $employee = Employee::whereHas('user', fn ($query) => $query->where('email', 'pegawai.baru@yapista.test'))->firstOrFail();
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

    public function test_qr_seeder_preserves_existing_tokens_and_skips_new_drafts(): void
    {
        $this->seedOnboarding();

        $employee = Employee::where('employee_number', '7770923822')->firstOrFail();
        $token = $employee->activeQrToken()->firstOrFail();
        $this->seed(EmployeeQrTokenSeeder::class);
        $this->assertSame(64, strlen($token->token_encrypted));
        $this->assertNotSame($employee->employee_number, $token->token_encrypted);
        $this->assertSame(hash('sha256', $token->token_encrypted), $token->token_hash);
        $this->assertNotSame($token->token_encrypted, $token->getRawOriginal('token_encrypted'));
        $this->assertTrue($token->isActive());

        $this->seed(EmployeeQrTokenSeeder::class);
        $this->assertSame(1, $employee->qrTokens()->where('is_active', true)->whereNull('revoked_at')->count());
        $this->assertSame($token->id, $employee->qrTokens()->where('is_active', true)->value('id'));
        $this->assertFalse(Employee::whereNull('employee_number')->firstOrFail()->qrTokens()->exists());
    }

    public function test_seed_data_promotes_only_listed_existing_employees_and_preserves_profile_history(): void
    {
        $this->seedMasterData();
        $admin = User::where('email', 'admin@yapista.test')->firstOrFail();
        $seeder = app(EmployeeSeeder::class);
        $rows = [
            $this->validRow(),
            array_merge($this->validRow(), [
                'employee_number' => '1000000002',
                'full_name' => 'Pegawai Rejected',
                'login_email' => 'pegawai.rejected@yapista.test',
            ]),
        ];

        $seeder->seedRows(array_map(
            fn (array $row): array => array_merge($row, ['employee_number' => null]),
            $rows,
        ));
        $first = Employee::whereHas('user', fn ($query) => $query->where('email', 'pegawai.validasi@yapista.test'))->firstOrFail();
        $second = Employee::whereHas('user', fn ($query) => $query->where('email', 'pegawai.rejected@yapista.test'))->firstOrFail();
        $first->forceFill([
            'verification_status' => 'draft',
            'phone' => '081200000001',
            'verified_by' => null,
            'verified_at' => null,
        ])->save();
        $second->forceFill([
            'verification_status' => 'rejected',
            'verification_note' => 'Catatan lama tetap tersimpan.',
            'verified_by' => $admin->id,
            'verified_at' => null,
        ])->save();
        $outside = Employee::create([
            'institution_id' => $first->institution_id,
            'position_id' => $first->position_id,
            'employee_number' => '1000000099',
            'full_name' => 'Pegawai Di Luar File',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ]);

        $summary = $seeder->seedRows($rows);

        $this->assertSame(2, $summary['promoted_to_verified']);
        $this->assertSame('verified', $first->fresh()->verification_status);
        $this->assertSame('1000000001', $first->fresh()->employee_number);
        $this->assertSame('081200000001', $first->phone);
        $this->assertNull($first->verified_at);
        $this->assertNull($first->verified_by);
        $this->assertSame('verified', $second->fresh()->verification_status);
        $this->assertSame('1000000002', $second->fresh()->employee_number);
        $this->assertSame('Catatan lama tetap tersimpan.', $second->verification_note);
        $this->assertSame($admin->id, $second->verified_by);
        $this->assertSame('draft', $outside->fresh()->verification_status);
        $this->assertNotNull($first->activeQrToken()->first());
        $this->assertNotNull($second->activeQrToken()->first());
    }

    public function test_existing_employee_with_empty_profile_has_id_card_and_is_not_in_new_employee_queue(): void
    {
        $this->seedOnboarding();
        $admin = User::where('email', 'admin@yapista.test')->firstOrFail();
        $existing = Employee::where('employee_number', '7770923822')->firstOrFail();
        $newEmployee = Employee::whereHas('user', fn ($query) => $query->where('email', 'pegawai.baru@yapista.test'))->firstOrFail();
        $newEmployee->forceFill(['verification_status' => 'submitted'])->save();

        $this->actingAs($admin)
            ->get(route('employees.id-card.show', $existing, absolute: false))
            ->assertOk()
            ->assertSee('7770923822')
            ->assertSee('QR Code');

        $this->actingAs($admin)
            ->get(route('verifications.index', absolute: false))
            ->assertOk()
            ->assertSee($newEmployee->full_name)
            ->assertDontSee($existing->full_name);
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
