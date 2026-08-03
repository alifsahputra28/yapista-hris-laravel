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

class ReportConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Institution $institution;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $this->institution = Institution::create([
            'name' => 'Unit Laporan',
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Jabatan Laporan',
            'type' => 'administratif',
            'status' => 'active',
        ]);
    }

    public function test_event_report_counts_only_active_participants_and_their_attendances(): void
    {
        $event = $this->event();
        $present = $this->employee('Peserta Hadir', '7770940001');
        $absent = $this->employee('Peserta Belum Hadir', '7770940002');
        $cancelled = $this->employee('Peserta Dibatalkan', '7770940003');

        $this->participant($event, $present);
        $this->participant($event, $absent);
        $this->participant($event, $cancelled, 'cancelled');
        $this->attendance($event, $present);
        $this->attendance($event, $cancelled);

        $this->actingAs($this->admin)
            ->get(route('reports.events', absolute: false))
            ->assertOk()
            ->assertViewHas('events', function ($events) use ($event): bool {
                $reportedEvent = collect($events->items())->firstWhere('id', $event->id);

                return $reportedEvent
                    && (int) $reportedEvent->active_participants_count === 2
                    && (int) $reportedEvent->active_attendances_count === 1;
            })
            ->assertViewHas('averageAttendance', 50.0);
    }

    public function test_report_pages_and_exports_are_restricted_to_admin_and_hr(): void
    {
        $event = $this->event();

        foreach (['super_admin', 'hr_admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($user)->get(route('reports.employees', absolute: false))->assertOk();
            $this->actingAs($user)->get(route('reports.events', absolute: false))->assertOk();
            $this->actingAs($user)->get(route('reports.events.attendances', $event, absolute: false))->assertOk();
            $this->actingAs($user)->get(route('reports.employees.export', absolute: false))->assertOk();
        }

        foreach (['panitia', 'pegawai'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($user)->get(route('reports.events', absolute: false))->assertForbidden();
            $this->actingAs($user)->get(route('reports.events.export', absolute: false))->assertForbidden();
        }

        auth()->logout();
        $this->get(route('reports.employees', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_event_report_percentage_is_zero_without_active_participants(): void
    {
        $event = $this->event();
        $cancelled = $this->employee('Cancelled Only', '7770940004');
        $this->participant($event, $cancelled, 'cancelled');
        $this->attendance($event, $cancelled);

        $this->actingAs($this->admin)
            ->get(route('reports.events', absolute: false))
            ->assertOk()
            ->assertViewHas('events', function ($events) use ($event): bool {
                $reportedEvent = collect($events->items())->firstWhere('id', $event->id);

                return $reportedEvent
                    && (int) $reportedEvent->active_participants_count === 0
                    && (int) $reportedEvent->active_attendances_count === 0;
            })
            ->assertViewHas('averageAttendance', 0.0);
    }

    public function test_attendance_detail_excludes_cancelled_and_has_consistent_summary(): void
    {
        $event = $this->event();
        $present = $this->employee('Aktif Hadir', '7770940005');
        $absent = $this->employee('Aktif Belum Hadir', '7770940006');
        $cancelled = $this->employee('Cancelled Historical', '7770940007');

        $this->participant($event, $present);
        $this->participant($event, $absent);
        $this->participant($event, $cancelled, 'cancelled');
        $this->attendance($event, $present, 'barcode');
        $this->attendance($event, $cancelled, 'manual');

        $this->actingAs($this->admin)
            ->get(route('reports.events.attendances', $event, absolute: false))
            ->assertOk()
            ->assertSee('Aktif Hadir')
            ->assertSee('Aktif Belum Hadir')
            ->assertDontSee('Cancelled Historical')
            ->assertViewHas('totalParticipants', 2)
            ->assertViewHas('attendedCount', 1)
            ->assertViewHas('absentCount', 1)
            ->assertViewHas('attendancePercentage', 50.0);
    }

    public function test_attendance_detail_filters_present_absent_unit_and_position(): void
    {
        $event = $this->event();
        $present = $this->employee('Filter Present', '7770940008');
        $absent = $this->employee('Filter Absent', '7770940009');
        $this->participant($event, $present);
        $this->participant($event, $absent);
        $this->attendance($event, $present);

        $this->actingAs($this->admin)
            ->get(route('reports.events.attendances', [
                'event' => $event,
                'attendance_status' => 'present',
                'institution_id' => $this->institution->id,
                'position_id' => $this->position->id,
            ], absolute: false))
            ->assertOk()
            ->assertSee('Filter Present')
            ->assertDontSee('Filter Absent');

        $this->actingAs($this->admin)
            ->get(route('reports.events.attendances', [
                'event' => $event,
                'attendance_status' => 'absent',
            ], absolute: false))
            ->assertOk()
            ->assertSee('Filter Absent')
            ->assertDontSee('Filter Present');
    }

    private function event(): Event
    {
        return Event::create([
            'name' => 'Kegiatan Laporan '.uniqid(),
            'event_date' => now()->toDateString(),
            'target_type' => 'all',
            'status' => 'closed',
            'created_by' => $this->admin->id,
        ]);
    }

    private function employee(string $name, string $employeeNumber): Employee
    {
        return Employee::create([
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'employee_number' => $employeeNumber,
            'full_name' => $name,
            'email' => str($name)->slug().uniqid().'@yapista.test',
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ]);
    }

    private function participant(Event $event, Employee $employee, string $status = 'invited'): EventParticipant
    {
        return EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'participant_status' => $status,
        ]);
    }

    private function attendance(Event $event, Employee $employee, string $method = 'barcode'): EventAttendance
    {
        return EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'scanned_by' => $this->admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => $method,
        ]);
    }
}
