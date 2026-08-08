<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use App\Services\EventParticipantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeNumberConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Institution $institution;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->institution = Institution::create([
            'name' => 'Unit Konsistensi NUP',
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Penguji NUP',
            'type' => 'administratif',
            'status' => 'active',
        ]);
    }

    public function test_employee_number_helper_accepts_exactly_ten_digits_including_leading_zero(): void
    {
        $employee = new Employee(['employee_number' => '0770923822']);

        $this->assertSame(10, Employee::EMPLOYEE_NUMBER_LENGTH);
        $this->assertTrue($employee->hasValidEmployeeNumber());

        foreach ([null, '', '777092382', '77709238222', 'ABC0923822', '777.923822'] as $invalid) {
            $employee->employee_number = $invalid;
            $this->assertFalse($employee->hasValidEmployeeNumber());
        }
    }

    public function test_legacy_fields_can_not_be_filled_through_mass_assignment(): void
    {
        $employee = new Employee;
        $employee->fill([
            'employee_number' => '7770924001',
            'nup' => '7770924998',
            'foundation_registry_number' => 25,
        ]);

        $this->assertSame('7770924001', $employee->employee_number);
        $this->assertNull($employee->getAttribute('nup'));
        $this->assertNull($employee->getAttribute('foundation_registry_number'));
    }

    public function test_admin_can_create_draft_employee_without_employee_number(): void
    {
        $this->actingAs($this->admin)
            ->post(route('employees.store', absolute: false), $this->employeePayload())
            ->assertRedirect(route('employees.index', absolute: false));

        $this->assertDatabaseHas('employees', [
            'email' => 'pegawai-nup@yapista.test',
            'employee_number' => null,
            'verification_status' => 'draft',
        ]);
    }

    public function test_employee_number_rejects_short_long_alphabetic_and_dotted_values(): void
    {
        foreach (['777092382', '77709238222', 'ABC0923822', '777.923822'] as $index => $value) {
            $payload = $this->employeePayload([
                'email' => "invalid-{$index}@yapista.test",
                'employee_number' => $value,
            ]);

            $this->actingAs($this->admin)
                ->post(route('employees.store', absolute: false), $payload)
                ->assertSessionHasErrors('employee_number');

            $this->assertDatabaseMissing('employees', ['email' => $payload['email']]);
        }
    }

    public function test_duplicate_employee_number_is_rejected(): void
    {
        $this->employee(['employee_number' => '7770924002']);

        $this->actingAs($this->admin)
            ->post(route('employees.store', absolute: false), $this->employeePayload([
                'email' => 'duplicate@yapista.test',
                'employee_number' => '7770924002',
            ]))
            ->assertSessionHasErrors('employee_number');

        $this->assertDatabaseMissing('employees', ['email' => 'duplicate@yapista.test']);
    }

    public function test_admin_can_set_and_clear_employee_number_before_verification(): void
    {
        $employee = $this->employee(['verification_status' => 'draft']);

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee, absolute: false), $this->employeePayload([
                'email' => $employee->email,
                'employee_number' => '7770924003',
            ]))
            ->assertRedirect(route('employees.index', absolute: false));

        $this->assertSame('7770924003', $employee->refresh()->employee_number);

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee, absolute: false), $this->employeePayload([
                'email' => $employee->email,
                'employee_number' => '',
            ]))
            ->assertRedirect(route('employees.index', absolute: false));

        $this->assertNull($employee->refresh()->employee_number);
    }

    public function test_admin_can_not_clear_employee_number_of_verified_employee(): void
    {
        $employee = $this->employee([
            'employee_number' => '7770924004',
            'verification_status' => 'verified',
        ]);

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee, absolute: false), $this->employeePayload([
                'email' => $employee->email,
                'employee_number' => '',
            ]))
            ->assertSessionHasErrors([
                'employee_number' => 'NUP / Nomor Pegawai wajib diisi untuk pegawai yang sudah terverifikasi.',
            ]);

        $this->assertSame('7770924004', $employee->refresh()->employee_number);
    }

    public function test_verified_employee_number_must_remain_valid_but_can_change_to_unique_value(): void
    {
        $employee = $this->employee([
            'employee_number' => '7770924005',
            'verification_status' => 'verified',
        ]);

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee, absolute: false), $this->employeePayload([
                'email' => $employee->email,
                'employee_number' => 'invalid',
            ]))
            ->assertSessionHasErrors('employee_number');

        $this->assertSame('7770924005', $employee->refresh()->employee_number);

        $this->actingAs($this->admin)
            ->put(route('employees.update', $employee, absolute: false), $this->employeePayload([
                'email' => $employee->email,
                'employee_number' => '7770924006',
            ]))
            ->assertRedirect(route('employees.index', absolute: false));

        $this->assertSame('7770924006', $employee->refresh()->employee_number);
    }

    public function test_approve_rejects_missing_and_invalid_employee_numbers(): void
    {
        foreach ([null, '777', '777.923822'] as $index => $employeeNumber) {
            $employee = $this->approvalReadyEmployee([
                'email' => "approval-invalid-{$index}@yapista.test",
                'employee_number' => $employeeNumber,
            ]);

            $expected = $employeeNumber === null
                ? 'NUP / Nomor Pegawai belum diisi.'
                : 'NUP / Nomor Pegawai harus terdiri dari 10 digit angka.';

            $this->actingAs($this->admin)
                ->post(route('verifications.approve', $employee, absolute: false))
                ->assertSessionHas('error', $expected);

            $this->assertSame('submitted', $employee->refresh()->verification_status);
        }
    }

    public function test_approve_accepts_valid_employee_number_without_generating_another_value(): void
    {
        $employee = $this->approvalReadyEmployee(['employee_number' => '7770924007']);

        $this->actingAs($this->admin)
            ->post(route('verifications.approve', $employee, absolute: false))
            ->assertRedirect(route('verifications.show', $employee, absolute: false));

        $this->assertSame('verified', $employee->refresh()->verification_status);
        $this->assertSame('7770924007', $employee->employee_number);
    }

    public function test_automatic_event_generation_only_includes_verified_active_employees_with_valid_numbers(): void
    {
        $valid = $this->employee([
            'email' => 'event-valid@yapista.test',
            'employee_number' => '7770924008',
            'verification_status' => 'verified',
        ]);
        $invalid = $this->employee([
            'email' => 'event-invalid@yapista.test',
            'employee_number' => 'ABC',
            'verification_status' => 'verified',
        ]);
        $missing = $this->employee([
            'email' => 'event-missing@yapista.test',
            'employee_number' => null,
            'verification_status' => 'verified',
        ]);
        $unverified = $this->employee([
            'email' => 'event-unverified@yapista.test',
            'employee_number' => '7770924009',
            'verification_status' => 'submitted',
        ]);
        $inactive = $this->employee([
            'email' => 'event-inactive@yapista.test',
            'employee_number' => '7770924010',
            'verification_status' => 'verified',
            'employment_status' => 'nonaktif',
        ]);
        $event = $this->event();

        $count = app(EventParticipantService::class)->replaceParticipants($event, 'all');

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('event_participants', ['event_id' => $event->id, 'employee_id' => $valid->id]);
        foreach ([$invalid, $missing, $unverified, $inactive] as $excluded) {
            $this->assertDatabaseMissing('event_participants', ['event_id' => $event->id, 'employee_id' => $excluded->id]);
        }
    }

    public function test_manual_participant_addition_skips_invalid_employee_number(): void
    {
        $valid = $this->employee([
            'email' => 'manual-valid@yapista.test',
            'employee_number' => '7770924011',
            'verification_status' => 'verified',
        ]);
        $invalid = $this->employee([
            'email' => 'manual-invalid@yapista.test',
            'employee_number' => '123',
            'verification_status' => 'verified',
        ]);
        $event = $this->event();

        $added = app(EventParticipantService::class)->addManualParticipants($event, [$valid->id, $invalid->id]);

        $this->assertSame(1, $added);
        $this->assertDatabaseHas('event_participants', ['event_id' => $event->id, 'employee_id' => $valid->id]);
        $this->assertDatabaseMissing('event_participants', ['event_id' => $event->id, 'employee_id' => $invalid->id]);
    }

    public function test_id_card_only_renders_qr_for_valid_verified_employee_with_active_token(): void
    {
        $invalid = $this->employee([
            'email' => 'card-invalid@yapista.test',
            'employee_number' => '777.0526.0025',
            'verification_status' => 'verified',
        ]);
        $valid = $this->employee([
            'email' => 'card-valid@yapista.test',
            'employee_number' => '7770924012',
            'verification_status' => 'verified',
        ]);

        $this->actingAs($this->admin)
            ->get(route('employees.id-card.show', $invalid, absolute: false))
            ->assertOk()
            ->assertViewHas('isValidForIdCard', false)
            ->assertViewHas('qrCodeSvg', null);

        $this->actingAs($this->admin)
            ->get(route('employees.id-card.show', $valid, absolute: false))
            ->assertOk()
            ->assertViewHas('isValidForIdCard', true)
            ->assertViewHas('qrCodeSvg', null)
            ->assertSee('QR Code belum tersedia.');

        app(EmployeeQrTokenService::class)->generate($valid, $this->admin);

        $this->actingAs($this->admin)
            ->get(route('employees.id-card.show', $valid, absolute: false))
            ->assertOk()
            ->assertViewHas('qrCodeSvg', fn ($qrCode): bool => is_string($qrCode) && str_contains($qrCode, '<svg'));
    }

    public function test_scanner_rejects_non_qr_input(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $event = $this->event(['status' => 'active']);

        $this->actingAs($panitia)
            ->post(route('events.scan', $event, absolute: false), ['qr_payload' => 'Call 777 0923'])
            ->assertSessionHas('error', 'QR Code tidak valid atau sudah tidak aktif.');

        $this->assertDatabaseCount('event_attendances', 0);
    }

    public function test_scanner_uses_qr_token_and_never_employee_number_or_legacy_nup(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $employee = $this->employee([
            'email' => 'scanner@yapista.test',
            'employee_number' => '7770924013',
            'verification_status' => 'verified',
        ]);
        $employee->nup = '7770924999';
        $employee->save();
        $event = $this->event(['status' => 'active']);
        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $employee->id]);
        $tokenService = app(EmployeeQrTokenService::class);
        $token = $tokenService->generate($employee, $panitia);

        $this->actingAs($panitia)
            ->post(route('events.scan', $event, absolute: false), ['qr_payload' => '7770924999'])
            ->assertSessionHas('error', 'QR Code tidak valid atau sudah tidak aktif.');

        $this->actingAs($panitia)
            ->post(route('events.scan', $event, absolute: false), ['qr_payload' => $tokenService->payloadFor($token)])
            ->assertSessionHas('success', 'Absensi berhasil dicatat.');

        $attendance = EventAttendance::query()->firstOrFail();
        $this->assertSame($employee->id, $attendance->employee_id);
        $this->assertSame('qr', $attendance->scan_method);
        $this->assertSame($token->id, $attendance->qr_token_id);
    }

    public function test_report_has_nup_filter_means_valid_employee_number_not_merely_non_null(): void
    {
        $valid = $this->employee([
            'full_name' => 'Laporan Valid',
            'email' => 'report-valid@yapista.test',
            'employee_number' => '7770924014',
        ]);
        $invalid = $this->employee([
            'full_name' => 'Laporan Invalid',
            'email' => 'report-invalid@yapista.test',
            'employee_number' => 'ABC',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reports.employees', ['employee_number_status' => 'filled'], absolute: false))
            ->assertOk()
            ->assertSee($valid->full_name)
            ->assertDontSee($invalid->full_name);

        $this->actingAs($this->admin)
            ->get(route('reports.employees', ['employee_number_status' => 'empty'], absolute: false))
            ->assertOk()
            ->assertSee($invalid->full_name)
            ->assertDontSee($valid->full_name);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function employeePayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Pegawai Konsistensi',
            'email' => 'pegawai-nup@yapista.test',
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function employee(array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'full_name' => 'Pegawai '.uniqid(),
            'email' => uniqid('employee').'@yapista.test',
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function approvalReadyEmployee(array $overrides = []): Employee
    {
        static $nikSequence = 0;

        $employee = $this->employee(array_merge([
            'nik' => '320101010101'.str_pad((string) ++$nikSequence, 4, '0', STR_PAD_LEFT),
            'phone' => '081234567890',
            'address' => 'Jl. Pengujian',
            'photo' => 'employees/photos/profile.jpg',
            'verification_status' => 'submitted',
        ], $overrides));

        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employee-documents/'.$employee->id.'/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'status' => 'valid',
            'uploaded_at' => now(),
        ]);

        return $employee;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'name' => 'Kegiatan '.uniqid(),
            'event_date' => now()->toDateString(),
            'target_type' => 'all',
            'created_by' => $this->admin->id,
            'status' => 'draft',
        ], $overrides));
    }
}
