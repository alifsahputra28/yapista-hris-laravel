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
use App\Support\IdCards\QrCodeRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeQrCodeTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private Position $position;

    private User $admin;

    private EmployeeQrTokenService $tokens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'Unit QR',
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Pegawai QR',
            'type' => 'administratif',
            'status' => 'active',
        ]);
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->tokens = app(EmployeeQrTokenService::class);
    }

    public function test_admin_and_hr_can_generate_qr_but_other_roles_cannot(): void
    {
        $adminEmployee = $this->employee('7770940001');
        $hrEmployee = $this->employee('7770940002');

        $this->actingAs($this->admin)
            ->post(route('employees.id-card.qr.generate', $adminEmployee, absolute: false))
            ->assertSessionHas('success', 'QR Code berhasil dibuat.');

        $this->actingAs(User::factory()->create(['role' => 'hr_admin']))
            ->post(route('employees.id-card.qr.generate', $hrEmployee, absolute: false))
            ->assertSessionHas('success');

        foreach (['panitia', 'pegawai'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('employees.id-card.qr.regenerate', $adminEmployee, absolute: false))
                ->assertForbidden();
        }

        auth()->logout();
        $this->post(route('employees.id-card.qr.generate', $adminEmployee, absolute: false))
            ->assertRedirect(route('login', absolute: false));

        $this->assertSame(1, $adminEmployee->qrTokens()->where('is_active', true)->count());
        $this->assertSame(1, $hrEmployee->qrTokens()->where('is_active', true)->count());
    }

    public function test_ineligible_employee_cannot_receive_qr(): void
    {
        $draft = $this->employee('7770940003', ['verification_status' => 'draft']);
        $invalid = $this->employee('invalid');
        $inactive = $this->employee('7770940004', ['employment_status' => 'nonaktif']);

        $cases = [
            [$draft, 'QR Code hanya tersedia untuk pegawai yang sudah terverifikasi.'],
            [$invalid, 'NUP / Nomor Pegawai harus terdiri dari 10 digit angka.'],
            [$inactive, 'Status kepegawaian tidak memenuhi syarat untuk memiliki QR Code.'],
        ];

        foreach ($cases as [$employee, $message]) {
            $this->actingAs($this->admin)
                ->post(route('employees.id-card.qr.generate', $employee, absolute: false))
                ->assertSessionHas('error', $message);
        }

        $this->assertDatabaseCount('employee_qr_tokens', 0);
    }

    public function test_token_is_random_hashed_encrypted_hidden_and_idempotent(): void
    {
        $employee = $this->employee('7770940005');
        $token = $this->tokens->generate($employee, $this->admin);
        $rawToken = $token->token_encrypted;

        $this->assertSame(64, strlen($rawToken));
        $this->assertNotSame($employee->employee_number, $rawToken);
        $this->assertNotSame((string) $employee->id, $rawToken);
        $this->assertSame(hash('sha256', $rawToken), $token->token_hash);
        $this->assertNotSame($rawToken, $token->getRawOriginal('token_encrypted'));
        $this->assertArrayNotHasKey('token_hash', $token->toArray());
        $this->assertArrayNotHasKey('token_encrypted', $token->toArray());

        $sameToken = $this->tokens->generate($employee, $this->admin);
        $this->assertSame($token->id, $sameToken->id);
        $this->assertSame(1, $employee->qrTokens()->where('is_active', true)->whereNull('revoked_at')->count());

        $storedCiphertext = DB::table('employee_qr_tokens')->where('id', $token->id)->value('token_encrypted');
        $this->assertNotSame($rawToken, $storedCiphertext);
    }

    public function test_secure_token_migration_preserves_existing_raw_token_as_hash_and_ciphertext(): void
    {
        $employee = $this->employee('7770940015');
        $migration = require database_path('migrations/2026_08_08_010000_secure_employee_qr_tokens_table.php');
        $migration->down();
        $rawToken = str_repeat('L', 64);
        $tokenId = DB::table('employee_qr_tokens')->insertGetId([
            'employee_id' => $employee->id,
            'token' => $rawToken,
            'is_active' => true,
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $stored = DB::table('employee_qr_tokens')->where('id', $tokenId)->first();
        $this->assertNotNull($stored);
        $this->assertSame(hash('sha256', $rawToken), $stored->token_hash);
        $this->assertNotSame($rawToken, $stored->token_encrypted);
        $this->assertSame($rawToken, Crypt::decryptString($stored->token_encrypted));
    }

    public function test_regenerate_revokes_old_token_without_changing_nup(): void
    {
        $employee = $this->employee('7770940006');
        $oldToken = $this->tokens->generate($employee, $this->admin);
        $oldPayload = $this->tokens->payloadFor($oldToken);
        $employeeNumber = $employee->employee_number;

        $newToken = $this->tokens->regenerate($employee, $this->admin);

        $this->assertNotSame($oldToken->id, $newToken->id);
        $this->assertFalse($oldToken->fresh()->isActive());
        $this->assertNotNull($oldToken->fresh()->revoked_at);
        $this->assertNull($this->tokens->resolvePayload($oldPayload));
        $this->assertSame($newToken->id, $this->tokens->resolvePayload($this->tokens->payloadFor($newToken))?->id);
        $this->assertSame($employeeNumber, $employee->fresh()->employee_number);
        $this->assertSame(1, $employee->qrTokens()->where('is_active', true)->whereNull('revoked_at')->count());
    }

    public function test_generate_consolidates_multiple_active_tokens_to_one(): void
    {
        $employee = $this->employee('7770940012');
        $first = $this->tokens->generate($employee, $this->admin);
        $rawToken = str_repeat('Z', 64);
        $second = EmployeeQrToken::create([
            'employee_id' => $employee->id,
            'token_hash' => hash('sha256', $rawToken),
            'token_encrypted' => $rawToken,
            'is_active' => true,
            'issued_at' => now(),
        ]);

        $active = $this->tokens->generate($employee, $this->admin);

        $this->assertSame($second->id, $active->id);
        $this->assertFalse($first->fresh()->isActive());
        $this->assertSame(1, $employee->qrTokens()->where('is_active', true)->whereNull('revoked_at')->count());
    }

    public function test_payload_is_strict_and_contains_no_employee_personal_data(): void
    {
        $employee = $this->employee('7770940007', [
            'full_name' => 'Rahasia QR',
            'email' => 'rahasia-qr@yapista.test',
            'nik' => '2171010101010007',
        ]);
        $token = $this->tokens->generate($employee, $this->admin);
        $payload = $this->tokens->payloadFor($token);

        $this->assertStringStartsWith(EmployeeQrTokenService::PAYLOAD_PREFIX, $payload);
        $this->assertStringNotContainsString($employee->employee_number, $payload);
        $this->assertNotSame(EmployeeQrTokenService::PAYLOAD_PREFIX.$employee->id, $payload);
        $this->assertStringNotContainsString($employee->full_name, $payload);
        $this->assertStringNotContainsString($employee->email, $payload);
        $this->assertStringNotContainsString((string) $employee->nik, $payload);
        $this->assertNull($this->tokens->parsePayload($employee->employee_number));
        $this->assertNull($this->tokens->parsePayload($token->token_encrypted));
        $this->assertNull($this->tokens->parsePayload('EMPLOYEE:'.$token->token_encrypted));
        $this->assertSame($token->token_encrypted, $this->tokens->parsePayload("  {$payload}\r\n"));
    }

    public function test_id_card_renders_qr_without_code128_or_raw_token(): void
    {
        $employee = $this->employee('7770940008');
        $token = $this->tokens->generate($employee, $this->admin);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.id-card.show', $employee, absolute: false));

        $response
            ->assertOk()
            ->assertSee('Pindai QR Code untuk absensi kegiatan')
            ->assertSee('employee-e-card', escape: false)
            ->assertSee($employee->employee_number)
            ->assertDontSee('Barcode belum tersedia')
            ->assertDontSee('Scan barcode')
            ->assertDontSee($token->token_encrypted)
            ->assertViewHas('qrCodeSvg', fn ($svg): bool => is_string($svg) && str_contains($svg, '<svg'));

        $svg = app(QrCodeRenderer::class)->render($this->tokens->payloadFor($token));
        $this->assertNotSame('', trim($svg));
        $this->assertStringContainsString('<svg', $svg);
    }

    public function test_employee_sees_only_own_qr_without_regenerate_action(): void
    {
        $owner = User::factory()->create(['role' => 'pegawai']);
        $employee = $this->employee('7770940009', ['user_id' => $owner->id]);
        $token = $this->tokens->generate($employee, $this->admin);

        $this->actingAs($owner)
            ->get(route('pegawai.id-card.show', absolute: false))
            ->assertOk()
            ->assertSee('Pindai QR Code untuk absensi kegiatan')
            ->assertSee('employee-e-card', escape: false)
            ->assertDontSee('Buat Ulang QR Code')
            ->assertDontSee($token->token_encrypted);

        $other = User::factory()->create(['role' => 'pegawai']);
        $this->actingAs($other)
            ->get(route('employees.id-card.show', $employee, absolute: false))
            ->assertForbidden();
    }

    public function test_scanner_accepts_only_active_qr_and_records_qr_method(): void
    {
        $scanner = User::factory()->create(['role' => 'panitia']);
        $employee = $this->employee('7770940010');
        $event = $this->event($employee);
        $token = $this->tokens->generate($employee, $this->admin);
        $payload = $this->tokens->payloadFor($token);

        foreach ([$employee->employee_number, $token->token_encrypted, 'teks acak'] as $invalidPayload) {
            $this->actingAs($scanner)
                ->postJson(route('events.scan', $event, absolute: false), ['qr_payload' => $invalidPayload])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'QR Code tidak valid atau sudah tidak aktif.');
        }

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), ['qr_payload' => $payload])
            ->assertOk()
            ->assertJsonPath('success', true);

        $attendance = EventAttendance::query()->firstOrFail();
        $this->assertSame('qr', $attendance->scan_method);
        $this->assertSame($token->id, $attendance->qr_token_id);

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), ['qr_payload' => $payload])
            ->assertConflict()
            ->assertJsonPath('status', 'already_attended');
        $this->assertDatabaseCount('event_attendances', 1);
    }

    public function test_revoked_qr_is_rejected_and_new_qr_is_accepted(): void
    {
        $scanner = User::factory()->create(['role' => 'panitia']);
        $employee = $this->employee('7770940011');
        $event = $this->event($employee);
        $oldToken = $this->tokens->generate($employee, $this->admin);
        $oldPayload = $this->tokens->payloadFor($oldToken);
        $newToken = $this->tokens->regenerate($employee, $this->admin);

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), ['qr_payload' => $oldPayload])
            ->assertUnprocessable();

        $this->actingAs($scanner)
            ->postJson(route('events.scan', $event, absolute: false), [
                'qr_payload' => $this->tokens->payloadFor($newToken),
            ])
            ->assertOk();

        $this->assertDatabaseHas('event_attendances', [
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'qr_token_id' => $newToken->id,
            'scan_method' => 'qr',
        ]);
    }

    public function test_legacy_barcode_history_and_manual_labels_remain_readable(): void
    {
        $this->assertSame('Barcode', (new EventAttendance(['scan_method' => 'barcode']))->scan_method_label);
        $this->assertSame('QR Code', (new EventAttendance(['scan_method' => 'qr']))->scan_method_label);
        $this->assertSame('Manual', (new EventAttendance(['scan_method' => 'manual']))->scan_method_label);
    }

    public function test_report_filters_and_export_label_qr_and_legacy_barcode(): void
    {
        $qrEmployee = $this->employee('7770940013', ['full_name' => 'Pegawai Metode QR']);
        $legacyEmployee = $this->employee('7770940014', ['full_name' => 'Pegawai Barcode Histori']);
        $event = Event::create([
            'name' => 'Laporan Metode Scan',
            'event_date' => now()->toDateString(),
            'target_type' => 'all',
            'status' => 'closed',
        ]);

        foreach ([$qrEmployee, $legacyEmployee] as $employee) {
            EventParticipant::create([
                'event_id' => $event->id,
                'employee_id' => $employee->id,
                'participant_status' => 'invited',
            ]);
        }

        EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $qrEmployee->id,
            'scanned_by' => $this->admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => 'qr',
        ]);
        EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $legacyEmployee->id,
            'scanned_by' => $this->admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => 'barcode',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reports.events.attendances', [$event, 'scan_method' => 'qr'], absolute: false))
            ->assertOk()
            ->assertSee('Pegawai Metode QR')
            ->assertDontSee('Pegawai Barcode Histori');

        $this->actingAs($this->admin)
            ->get(route('reports.events.attendances', [$event, 'scan_method' => 'barcode'], absolute: false))
            ->assertOk()
            ->assertSee('Pegawai Barcode Histori')
            ->assertDontSee('Pegawai Metode QR');

        $response = $this->actingAs($this->admin)
            ->get(route('reports.events.attendances.export', $event, absolute: false));
        $response->assertOk()->assertDownload();

        $content = $response->streamedContent();
        $this->assertStringContainsString('QR Code', $content);
        $this->assertStringContainsString('Barcode', $content);
        $this->assertStringNotContainsString('YAPISTA:EMPLOYEE:', $content);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function employee(string $employeeNumber, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'full_name' => 'Pegawai QR '.uniqid(),
            'email' => uniqid('qr').'@yapista.test',
            'employee_number' => $employeeNumber,
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ], $overrides));
    }

    private function event(Employee $employee): Event
    {
        $event = Event::create([
            'name' => 'Kegiatan QR '.uniqid(),
            'event_date' => now()->toDateString(),
            'target_type' => 'all',
            'status' => 'active',
        ]);

        EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'participant_status' => 'invited',
        ]);

        return $event;
    }
}
