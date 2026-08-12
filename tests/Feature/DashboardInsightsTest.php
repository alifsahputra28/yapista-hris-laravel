<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardInsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
    }

    public function test_dashboard_uses_real_employee_event_and_attendance_aggregates(): void
    {
        [$unitA, $positionA] = $this->masterData('Unit Akademik');
        [$unitB, $positionB] = $this->masterData('Kantor Yayasan');

        $present = $this->employee($unitA, $positionA, 'Pegawai Hadir', '7770960001', 'guru');
        $absent = $this->employee($unitA, $positionA, 'Pegawai Belum Hadir', '7770960002', 'guru');
        $cancelled = $this->employee($unitB, $positionB, 'Peserta Dibatalkan', '7770960003', 'dosen');
        $this->employee($unitB, $positionB, 'Pegawai Nonaktif', '7770960004', 'dosen', 'nonaktif');

        $event = Event::create([
            'name' => 'Evaluasi Semester',
            'event_date' => now()->toDateString(),
            'target_type' => 'all',
            'status' => 'closed',
            'created_by' => $this->admin->id,
        ]);

        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $present->id, 'participant_status' => 'invited']);
        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $absent->id, 'participant_status' => 'invited']);
        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $cancelled->id, 'participant_status' => 'cancelled']);

        $this->attendance($event, $present);
        $this->attendance($event, $cancelled);

        $this->actingAs($this->admin)
            ->get(route('dashboard', absolute: false))
            ->assertOk()
            ->assertSee('employee-unit-chart', false)
            ->assertSee('employee-composition-chart', false)
            ->assertSee('attendance-trend-chart', false)
            ->assertViewHas('dashboard', function (array $dashboard): bool {
                return $dashboard['kpis']['totalEmployees'] === 4
                    && $dashboard['kpis']['activeEmployees'] === 3
                    && $dashboard['kpis']['eventsThisMonth'] === 1
                    && $dashboard['kpis']['averageAttendance'] === 50.0
                    && $dashboard['institutionDistribution']['labels'] === ['Unit Akademik', 'Kantor Yayasan']
                    && $dashboard['institutionDistribution']['values'] === [2, 1]
                    && $dashboard['employeeComposition']['labels'] === ['Guru', 'Dosen']
                    && $dashboard['employeeComposition']['values'] === [2, 1]
                    && $dashboard['attendanceTrend']['percentages'] === [50.0]
                    && $dashboard['attendanceTrend']['attended'] === [1]
                    && $dashboard['attendanceTrend']['participants'] === [2];
            });
    }

    public function test_dashboard_renders_compact_empty_states_when_chart_data_is_unavailable(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard', absolute: false))
            ->assertOk()
            ->assertSee('Belum ada data pegawai aktif')
            ->assertSee('Belum ada data komposisi')
            ->assertSee('Belum ada data kehadiran');
    }

    /**
     * @return array{Institution, Position}
     */
    private function masterData(string $name): array
    {
        $institution = Institution::create([
            'name' => $name,
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Jabatan '.$name,
            'type' => 'administratif',
            'status' => 'active',
        ]);

        return [$institution, $position];
    }

    private function employee(
        Institution $institution,
        Position $position,
        string $name,
        string $number,
        string $type,
        string $status = 'aktif',
    ): Employee {
        return Employee::create([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => $number,
            'full_name' => $name,
            'email' => str($name)->slug().$number.'@yapista.test',
            'employee_type' => $type,
            'employment_status' => $status,
            'verification_status' => 'verified',
        ]);
    }

    private function attendance(Event $event, Employee $employee): EventAttendance
    {
        return EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'scanned_by' => $this->admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => 'qr',
        ]);
    }
}
