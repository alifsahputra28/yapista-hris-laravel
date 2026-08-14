<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceQueryAuditTest extends TestCase
{
    use RefreshDatabase;

    private bool $captureQueries = false;

    private int $selectQueries = 0;

    protected function setUp(): void
    {
        parent::setUp();

        DB::listen(function (QueryExecuted $query): void {
            if ($this->captureQueries && str_starts_with(strtolower(ltrim($query->sql)), 'select')) {
                $this->selectQueries++;
            }
        });
    }

    public function test_core_routes_keep_bounded_query_counts(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        $pegawaiUser = User::factory()->create(['role' => 'pegawai', 'status' => 'active']);
        $institution = Institution::create(['name' => 'Unit Query Audit', 'level' => 'Unit', 'status' => 'active']);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Jabatan Query Audit',
            'type' => 'administratif',
            'status' => 'active',
        ]);

        $employees = collect(range(1, 25))->map(fn (int $index): Employee => Employee::create([
            'user_id' => $index === 1 ? $pegawaiUser->id : null,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => '7771'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
            'full_name' => 'Pegawai Query '.$index,
            'email' => "query{$index}@yapista.test",
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => $index === 1 || $index > 20 ? 'verified' : 'submitted',
        ]));

        foreach (range(1, 5) as $index) {
            EmployeeDocument::create([
                'employee_id' => $employees->first()->id,
                'document_type' => 'dokumen_lain_'.$index,
                'document_slot' => 'primary',
                'file_path' => "employees/query-audit/document-{$index}.pdf",
                'original_name' => "document-{$index}.pdf",
                'status' => 'pending',
                'uploaded_at' => now(),
            ]);
        }

        foreach (range(1, 20) as $index) {
            Event::create([
                'name' => 'Kegiatan Query '.$index,
                'event_date' => now()->addDays($index)->toDateString(),
                'target_type' => 'all',
                'status' => 'active',
                'created_by' => $admin->id,
            ]);
        }

        $scanEvent = Event::query()->firstOrFail();
        EventParticipant::create([
            'event_id' => $scanEvent->id,
            'employee_id' => $employees->first()->id,
            'participant_status' => 'invited',
        ]);
        $qrService = app(EmployeeQrTokenService::class);
        $qrToken = $qrService->generate($employees->first(), $admin);

        $scannerScanCount = $this->selectCount(fn () => $this->actingAs($panitia)->postJson(route('events.scan', $scanEvent, absolute: false), [
            'qr_payload' => $qrService->payloadFor($qrToken),
        ])->assertOk());
        $attendanceCount = $this->selectCount(fn () => $this->actingAs($panitia)->get(route('events.attendances.index', $scanEvent, absolute: false))->assertOk());

        $counts = [
            'employees' => $this->selectCount(fn () => $this->actingAs($admin)->get(route('employees.index', absolute: false))->assertOk()),
            'verifications' => $this->selectCount(fn () => $this->actingAs($admin)->get(route('verifications.index', absolute: false))->assertOk()),
            'dashboard' => $this->selectCount(fn () => $this->actingAs($admin)->get(route('dashboard', absolute: false))->assertOk()),
            'events' => $this->selectCount(fn () => $this->actingAs($admin)->get(route('events.index', absolute: false))->assertOk()),
            'employee_report' => $this->selectCount(fn () => $this->actingAs($admin)->get(route('reports.employees', absolute: false))->assertOk()),
            'event_report' => $this->selectCount(fn () => $this->actingAs($admin)->get(route('reports.events', absolute: false))->assertOk()),
            'scanner_dashboard' => $this->selectCount(fn () => $this->actingAs($panitia)->get(route('scanner.dashboard', absolute: false))->assertOk()),
            'scanner_scan' => $scannerScanCount,
            'attendance' => $attendanceCount,
            'employee_documents' => $this->selectCount(fn () => $this->actingAs($pegawaiUser)->get(route('pegawai.documents.index', absolute: false))->assertOk()),
            'employee_dashboard' => $this->selectCount(fn () => $this->actingAs($pegawaiUser)->get(route('pegawai.dashboard', absolute: false))->assertOk()),
            'employee_profile' => $this->selectCount(fn () => $this->actingAs($pegawaiUser)->get(route('pegawai.profile.show', absolute: false))->assertOk()),
            'employee_activities' => $this->selectCount(fn () => $this->actingAs($pegawaiUser)->get(route('pegawai.activities.index', absolute: false))->assertOk()),
        ];

        $limits = [
            'employees' => 9,
            'verifications' => 9,
            'dashboard' => 8,
            'events' => 6,
            'employee_report' => 10,
            'event_report' => 7,
            'scanner_dashboard' => 3,
            'scanner_scan' => 10,
            'attendance' => 16,
            'employee_documents' => 4,
            'employee_dashboard' => 10,
            'employee_profile' => 10,
            'employee_activities' => 10,
        ];

        foreach ($limits as $route => $limit) {
            $this->assertLessThanOrEqual($limit, $counts[$route], "Query count {$route} melebihi batas {$limit}.");
        }
    }

    private function selectCount(callable $request): int
    {
        $this->selectQueries = 0;
        $this->captureQueries = true;

        try {
            $request();
        } finally {
            $this->captureQueries = false;
        }

        return $this->selectQueries;
    }
}
