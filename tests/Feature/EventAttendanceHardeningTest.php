<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use App\Services\EventAttendanceService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use Tests\TestCase;

class EventAttendanceHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private Position $position;

    private int $employeeSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Unit Attendance',
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Peserta Attendance',
            'type' => 'administratif',
            'status' => 'active',
        ]);
    }

    public function test_scanner_access_matches_role_rules(): void
    {
        $event = $this->event();

        foreach (['super_admin', 'hr_admin', 'panitia'] as $role) {
            $user = $this->user($role);

            $this->actingAs($user)
                ->get(route('events.scanner', $event, absolute: false))
                ->assertOk();
            $this->actingAs($user)
                ->get(route('events.attendances.index', $event, absolute: false))
                ->assertOk();
        }

        $pegawai = $this->user('pegawai');
        $this->actingAs($pegawai)
            ->get(route('events.scanner', $event, absolute: false))
            ->assertForbidden();
        $this->actingAs($pegawai)
            ->get(route('events.attendances.index', $event, absolute: false))
            ->assertForbidden();

        auth()->logout();

        $this->get(route('events.scanner', $event, absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_only_admin_and_hr_can_delete_attendance_and_closed_event_is_protected(): void
    {
        $event = $this->event();
        $employee = $this->employee(['employee_number' => '7770930001']);
        $this->participant($event, $employee);
        $attendance = $this->attendance($event, $employee, $this->user('panitia'));

        $this->actingAs($this->user('panitia'))
            ->delete(route('event-attendances.destroy', $attendance, absolute: false))
            ->assertForbidden();
        $this->assertDatabaseHas('event_attendances', ['id' => $attendance->id]);

        $this->actingAs($this->user('hr_admin'))
            ->delete(route('event-attendances.destroy', $attendance, absolute: false))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('event_attendances', ['id' => $attendance->id]);

        $adminAttendance = $this->attendance($event, $employee, $this->user('panitia'));
        $this->actingAs($this->user('super_admin'))
            ->delete(route('event-attendances.destroy', $adminAttendance, absolute: false))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('event_attendances', ['id' => $adminAttendance->id]);

        $closedEvent = $this->event(['status' => 'closed']);
        $closedAttendance = $this->attendance($closedEvent, $employee, $this->user('panitia'));

        $this->actingAs($this->user('super_admin'))
            ->delete(route('event-attendances.destroy', $closedAttendance, absolute: false))
            ->assertSessionHas('error', 'Absensi tidak dapat dihapus karena kegiatan sudah ditutup.');
        $this->assertDatabaseHas('event_attendances', ['id' => $closedAttendance->id]);
    }

    public function test_valid_qr_scan_records_complete_attendance_and_returns_employee(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $employee = $this->employee([
            'full_name' => 'Ahmad Scanner',
            'employee_number' => '7770930002',
        ]);
        $this->participant($event, $employee);
        $payload = $this->qrPayload($employee, $scanner);

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), [
                'qr_payload' => $payload,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('employee.full_name', 'Ahmad Scanner');

        $attendance = EventAttendance::query()->firstOrFail();
        $this->assertSame($event->id, $attendance->event_id);
        $this->assertSame($employee->id, $attendance->employee_id);
        $this->assertSame($scanner->id, $attendance->scanned_by);
        $this->assertNotNull($attendance->scanned_at);
        $this->assertSame('qr', $attendance->scan_method);
        $this->assertNotNull($attendance->qr_token_id);
        $this->assertSame('present', $attendance->attendance_status);
    }

    public function test_scanner_rejects_empty_nup_and_invalid_qr_payloads(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();

        $cases = [
            ['', 'QR Code wajib dipindai.'],
            ['777093001', 'QR Code tidak valid atau sudah tidak aktif.'],
            ['7770930002', 'QR Code tidak valid atau sudah tidak aktif.'],
            ['3201010101010001', 'QR Code tidak valid atau sudah tidak aktif.'],
            ['YAPISTA', 'QR Code tidak valid atau sudah tidak aktif.'],
            ['YAPISTA:EMPLOYEE:', 'QR Code tidak valid atau sudah tidak aktif.'],
            ['YAPISTA:EMPLOYEE:unknown', 'QR Code tidak valid atau sudah tidak aktif.'],
            ['YAPISTA:EMPLOYEE:'.str_repeat('A', 4_096), 'QR Code tidak valid atau sudah tidak aktif.'],
            ["YAPISTA:EMPLOYEE:abc\ndef", 'QR Code tidak valid atau sudah tidak aktif.'],
        ];

        foreach ($cases as [$input, $message]) {
            $this->actingAs($scanner)
                ->postJson(route('events.scan', $event, absolute: false), ['qr_payload' => $input])
                ->assertUnprocessable()
                ->assertJsonPath('message', $message);
        }

        $this->assertDatabaseCount('event_attendances', 0);
    }

    public function test_scanner_trims_outer_whitespace_but_rejects_modified_payload(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $employee = $this->employee(['employee_number' => '7770930003']);
        $this->participant($event, $employee);
        $payload = $this->qrPayload($employee, $scanner);

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), ['qr_payload' => "  {$payload}\r\n"])
            ->assertOk();

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), ['qr_payload' => str_replace(':', ' : ', $payload)])
            ->assertUnprocessable();

        $this->assertDatabaseCount('event_attendances', 1);
    }

    public function test_draft_closed_and_cancelled_events_reject_new_attendance(): void
    {
        $scanner = $this->user('panitia');
        $employee = $this->employee(['employee_number' => '7770930005']);
        $statuses = [
            'draft' => 'Kegiatan belum diaktifkan.',
            'closed' => 'Kegiatan sudah ditutup.',
            'cancelled' => 'Kegiatan telah dibatalkan.',
        ];

        foreach ($statuses as $status => $message) {
            $event = $this->event(['status' => $status]);
            $this->participant($event, $employee);
            $payload = $this->qrPayload($employee, $scanner);

            $this->actingAs($scanner)
                ->postJson(route('events.scan', $event, absolute: false), [
                    'qr_payload' => $payload,
                ])
                ->assertUnprocessable()
                ->assertJsonPath('message', $message);
        }

        $this->assertDatabaseCount('event_attendances', 0);
    }

    public function test_employee_and_participant_rules_are_enforced(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $unverified = $this->employee([
            'employee_number' => '7770930006',
            'verification_status' => 'submitted',
        ]);
        $nonParticipant = $this->employee(['employee_number' => '7770930007']);
        $cancelled = $this->employee(['employee_number' => '7770930008']);
        $inactive = $this->employee([
            'employee_number' => '7770930009',
        ]);
        $this->participant($event, $unverified);
        $this->participant($event, $cancelled, 'cancelled');
        $this->participant($event, $inactive);

        $unverified->update(['verification_status' => 'verified']);
        $payloads = [
            $unverified->id => $this->qrPayload($unverified, $scanner),
            $nonParticipant->id => $this->qrPayload($nonParticipant, $scanner),
            $cancelled->id => $this->qrPayload($cancelled, $scanner),
            $inactive->id => $this->qrPayload($inactive, $scanner),
        ];
        $unverified->update(['verification_status' => 'submitted']);
        $inactive->update(['employment_status' => 'nonaktif']);

        $cases = [
            [$unverified, 'Pegawai belum terverifikasi.'],
            [$nonParticipant, 'Pegawai tidak terdaftar sebagai peserta kegiatan.'],
            [$cancelled, 'Keikutsertaan pegawai pada kegiatan ini telah dibatalkan.'],
            [$inactive, 'Status kepegawaian tidak memenuhi syarat untuk melakukan absensi.'],
        ];

        foreach ($cases as [$employee, $message]) {
            $this->actingAs($scanner)
                ->postJson(route('events.scan', $event, absolute: false), [
                    'qr_payload' => $payloads[$employee->id],
                ])
                ->assertUnprocessable()
                ->assertJsonPath('message', $message);
        }

        $this->assertDatabaseCount('event_attendances', 0);
    }

    public function test_service_rejects_employee_with_invalid_number(): void
    {
        $event = $this->event();
        $employee = $this->employee(['employee_number' => 'invalid']);
        $this->participant($event, $employee);
        $token = $this->rawQrToken($employee);

        $result = app(EventAttendanceService::class)
            ->recordQrAttendance($event, $employee, $this->user('panitia'), $token);

        $this->assertSame('rejected', $result->status);
        $this->assertSame('Pegawai belum memiliki NUP / Nomor Pegawai yang valid.', $result->message);
        $this->assertDatabaseCount('event_attendances', 0);
    }

    public function test_second_scan_returns_already_attended_without_creating_another_row(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $employee = $this->employee([
            'full_name' => 'Siti Duplikat',
            'employee_number' => '7770930010',
        ]);
        $this->participant($event, $employee);
        $payload = $this->qrPayload($employee, $scanner);

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), [
                'qr_payload' => $payload,
            ])
            ->assertOk();

        $response = $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), [
                'qr_payload' => $payload,
            ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath('status', 'already_attended')
            ->assertJson(fn ($json) => $json->whereType('message', 'string')
                ->where('success', false)
                ->etc());

        $this->assertStringContainsString(
            'Siti Duplikat sudah melakukan absensi pada',
            $response->json('message')
        );
        $this->assertDatabaseCount('event_attendances', 1);
    }

    public function test_qr_and_manual_attendance_reject_each_other_as_duplicates(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $qrFirst = $this->employee(['employee_number' => '7770930011']);
        $manualFirst = $this->employee(['employee_number' => '7770930012']);
        $this->participant($event, $qrFirst);
        $this->participant($event, $manualFirst);
        $qrFirstPayload = $this->qrPayload($qrFirst, $scanner);
        $manualFirstPayload = $this->qrPayload($manualFirst, $scanner);

        $this->actingAs($scanner)
            ->post(route('events.scan', $event, absolute: false), [
                'qr_payload' => $qrFirstPayload,
            ])
            ->assertSessionHas('success');
        $this->actingAs($scanner)
            ->post(route('events.attendances.manual', $event, absolute: false), [
                'employee_id' => $qrFirst->id,
            ])
            ->assertSessionHas('warning');

        $this->actingAs($scanner)
            ->post(route('events.attendances.manual', $event, absolute: false), [
                'employee_id' => $manualFirst->id,
                'note' => 'QR Code rusak',
            ])
            ->assertSessionHas('success');
        $this->actingAs($scanner)
            ->post(route('events.scan', $event, absolute: false), [
                'qr_payload' => $manualFirstPayload,
            ])
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('event_attendances', 2);
    }

    public function test_manual_attendance_uses_shared_validation_and_records_scanner_data(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $employee = $this->employee(['employee_number' => '7770930013']);
        $this->participant($event, $employee);

        $this->actingAs($scanner)
            ->post(route('events.attendances.manual', $event, absolute: false), [
                'employee_id' => $employee->id,
                'note' => 'Scanner fisik bermasalah',
            ])
            ->assertSessionHas('success');

        $attendance = EventAttendance::query()->firstOrFail();
        $this->assertSame('manual', $attendance->scan_method);
        $this->assertSame('present', $attendance->attendance_status);
        $this->assertSame($scanner->id, $attendance->scanned_by);
        $this->assertNotNull($attendance->scanned_at);
        $this->assertSame('Scanner fisik bermasalah', $attendance->note);

        $otherEvent = $this->event();
        $this->actingAs($scanner)
            ->post(route('events.attendances.manual', $otherEvent, absolute: false), [
                'employee_id' => $employee->id,
            ])
            ->assertSessionHas('error', 'Pegawai tidak terdaftar sebagai peserta kegiatan.');

        $draftEvent = $this->event(['status' => 'draft']);
        $this->participant($draftEvent, $employee);
        $this->actingAs($scanner)
            ->post(route('events.attendances.manual', $draftEvent, absolute: false), [
                'employee_id' => $employee->id,
            ])
            ->assertSessionHas('error', 'Kegiatan belum diaktifkan.');
    }

    public function test_duplicate_constraint_race_becomes_already_attended_result(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $employee = $this->employee([
            'full_name' => 'Pegawai Race',
            'employee_number' => '7770930014',
        ]);
        $this->participant($event, $employee);
        $existing = $this->attendance($event, $employee, $scanner);
        $qrToken = $this->rawQrToken($employee);
        $exception = $this->uniqueException(['event_id', 'employee_id']);

        $service = new class($existing, $exception) extends EventAttendanceService
        {
            private int $findCalls = 0;

            public function __construct(
                private readonly EventAttendance $existing,
                private readonly UniqueConstraintViolationException $exception,
            ) {}

            protected function findExistingAttendance(Event $event, Employee $employee): ?EventAttendance
            {
                return ++$this->findCalls === 1 ? null : $this->existing;
            }

            protected function createAttendance(array $attributes): EventAttendance
            {
                throw $this->exception;
            }
        };

        $result = $service->recordQrAttendance($event, $employee, $scanner, $qrToken);

        $this->assertSame('already_attended', $result->status);
        $this->assertSame($existing->id, $result->attendance?->id);
        $this->assertStringContainsString('Pegawai Race sudah melakukan absensi pada', $result->message);
        $this->assertDatabaseCount('event_attendances', 1);
    }

    public function test_non_attendance_unique_and_non_duplicate_database_exceptions_are_not_hidden(): void
    {
        $scanner = $this->user('panitia');
        $event = $this->event();
        $employee = $this->employee(['employee_number' => '7770930015']);
        $this->participant($event, $employee);
        $qrToken = $this->rawQrToken($employee);
        $wrongUnique = $this->uniqueException(['another_column']);

        $uniqueService = new class($wrongUnique) extends EventAttendanceService
        {
            public function __construct(private readonly UniqueConstraintViolationException $exception) {}

            protected function createAttendance(array $attributes): EventAttendance
            {
                throw $this->exception;
            }
        };

        try {
            $uniqueService->recordQrAttendance($event, $employee, $scanner, $qrToken);
            $this->fail('Unique constraint lain seharusnya dilempar kembali.');
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertSame($wrongUnique, $exception);
        }

        $queryException = new QueryException(
            'sqlite',
            'insert into event_attendances',
            [],
            new PDOException('database is locked')
        );
        $queryService = new class($queryException) extends EventAttendanceService
        {
            public function __construct(private readonly QueryException $exception) {}

            protected function createAttendance(array $attributes): EventAttendance
            {
                throw $this->exception;
            }
        };

        $this->expectException(QueryException::class);
        $queryService->recordQrAttendance($event, $employee, $scanner, $qrToken);
    }

    public function test_summary_excludes_cancelled_participants_and_their_historical_attendance(): void
    {
        $admin = $this->user('super_admin');
        $event = $this->event();
        $active = $this->employee(['employee_number' => '7770930016']);
        $cancelled = $this->employee(['employee_number' => '7770930017']);
        $this->participant($event, $active);
        $this->participant($event, $cancelled, 'cancelled');
        $this->attendance($event, $active, $admin);
        $this->attendance($event, $cancelled, $admin);

        $this->actingAs($admin)
            ->get(route('events.attendances.index', $event, absolute: false))
            ->assertOk()
            ->assertViewHas('totalParticipants', 1)
            ->assertViewHas('attendedCount', 1)
            ->assertViewHas('absentCount', 0)
            ->assertViewHas('attendancePercentage', 100.0);

        $emptyEvent = $this->event();
        $this->actingAs($admin)
            ->get(route('events.attendances.index', $emptyEvent, absolute: false))
            ->assertOk()
            ->assertViewHas('totalParticipants', 0)
            ->assertViewHas('attendedCount', 0)
            ->assertViewHas('absentCount', 0)
            ->assertViewHas('attendancePercentage', 0);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
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
            'status' => 'active',
        ], $overrides));
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
            'email' => uniqid('attendance').'@yapista.test',
            'employee_number' => '777093'.str_pad((string) ++$this->employeeSequence, 4, '0', STR_PAD_LEFT),
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ], $overrides));
    }

    private function participant(Event $event, Employee $employee, string $status = 'invited'): EventParticipant
    {
        return EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'participant_status' => $status,
        ]);
    }

    private function attendance(Event $event, Employee $employee, User $scanner): EventAttendance
    {
        return EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'scanned_by' => $scanner->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => 'barcode',
        ]);
    }

    private function qrPayload(Employee $employee, User $creator): string
    {
        $service = app(EmployeeQrTokenService::class);

        return $service->payloadFor($service->generate($employee, $creator));
    }

    private function rawQrToken(Employee $employee): EmployeeQrToken
    {
        $rawToken = str_repeat('A', 60).str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT);

        return EmployeeQrToken::create([
            'employee_id' => $employee->id,
            'token_hash' => hash('sha256', $rawToken),
            'token_encrypted' => $rawToken,
            'is_active' => true,
            'issued_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $columns
     */
    private function uniqueException(array $columns): UniqueConstraintViolationException
    {
        $exception = new UniqueConstraintViolationException(
            'sqlite',
            'insert into event_attendances',
            [],
            new PDOException('UNIQUE constraint failed')
        );

        return $exception->setColumns($columns);
    }
}
