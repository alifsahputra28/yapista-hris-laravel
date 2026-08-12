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

class FilterStandardizationTest extends TestCase
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
            'name' => 'Unit Filter',
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Jabatan Filter',
            'type' => 'fungsional',
            'status' => 'active',
        ]);
    }

    public function test_employee_filters_support_employee_type_and_keep_nik_lookup_secure(): void
    {
        $matching = $this->employee('Pegawai Guru Filter', '7770960001', ['employee_type' => 'guru']);
        $this->employee('Pegawai Dosen Filter', '7770960002', ['employee_type' => 'dosen']);

        $response = $this->actingAs($this->admin)->get(route('employees.index', [
            'employee_type' => 'guru',
            'institution_id' => $this->institution->id,
        ], absolute: false));

        $response
            ->assertOk()
            ->assertSee($matching->full_name)
            ->assertDontSee('Pegawai Dosen Filter')
            ->assertSee('Filter Lanjutan')
            ->assertSee('Filter aktif:')
            ->assertSee('Cari berdasarkan NIK')
            ->assertSee(route('employees.nik-search', absolute: false))
            ->assertSee('method="POST"', false)
            ->assertDontSee('?nik=', false);
    }

    public function test_participant_filters_are_applied_and_pagination_preserves_query(): void
    {
        $event = $this->event(['status' => 'active']);

        for ($index = 1; $index <= 22; $index++) {
            $employee = $this->employee(
                'Target Peserta '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                '777096'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            );
            EventParticipant::create([
                'event_id' => $event->id,
                'employee_id' => $employee->id,
                'participant_status' => 'invited',
            ]);
        }

        $otherInstitution = Institution::create(['name' => 'Unit Lain', 'status' => 'active']);
        $otherPosition = Position::create([
            'institution_id' => $otherInstitution->id,
            'name' => 'Jabatan Lain',
            'status' => 'active',
        ]);
        $excluded = Employee::create([
            'institution_id' => $otherInstitution->id,
            'position_id' => $otherPosition->id,
            'full_name' => 'Target Tidak Sesuai',
            'employee_number' => '7770969999',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ]);
        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $excluded->id, 'participant_status' => 'invited']);

        $response = $this->actingAs($this->admin)->get(route('events.participants.index', [
            'event' => $event,
            'search' => 'Target',
            'participant_status' => 'invited',
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
        ], absolute: false));

        $response
            ->assertOk()
            ->assertSee('Target Peserta 01')
            ->assertDontSee($excluded->full_name)
            ->assertSee('participant_status=invited', false)
            ->assertSee('position_id='.$this->position->id, false)
            ->assertSee('Filter Lanjutan');
    }

    public function test_attendance_method_filter_uses_existing_attendance_records(): void
    {
        $event = $this->event(['status' => 'active']);
        $qrEmployee = $this->employee('Peserta QR', '7770960101');
        $manualEmployee = $this->employee('Peserta Manual', '7770960102');

        foreach ([$qrEmployee, $manualEmployee] as $employee) {
            EventParticipant::create(['event_id' => $event->id, 'employee_id' => $employee->id, 'participant_status' => 'invited']);
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
            'employee_id' => $manualEmployee->id,
            'scanned_by' => $this->admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => 'manual',
        ]);

        $this->actingAs($this->admin)
            ->get(route('events.attendances.index', ['event' => $event, 'scan_method' => 'qr'], absolute: false))
            ->assertOk()
            ->assertSee($qrEmployee->full_name)
            ->assertDontSee($manualEmployee->full_name)
            ->assertSee('Metode:')
            ->assertSee('QR Code');
    }

    public function test_report_filters_render_compact_advanced_controls_and_active_chips(): void
    {
        $this->employee('Pegawai Laporan Filter', '7770960201', ['employee_type' => 'guru']);

        $this->actingAs($this->admin)
            ->get(route('reports.employees', [
                'institution_id' => $this->institution->id,
                'employee_type' => 'guru',
                'verification_status' => 'verified',
            ], absolute: false))
            ->assertOk()
            ->assertSee('Filter Lanjutan')
            ->assertSee('Filter aktif:')
            ->assertSee('Reset semua')
            ->assertSee('Pegawai Laporan Filter');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function employee(string $name, string $number, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'full_name' => $name,
            'email' => str($name)->slug().$number.'@yapista.test',
            'employee_number' => $number,
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'name' => 'Kegiatan Filter',
            'event_date' => now()->toDateString(),
            'target_type' => 'all',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ], $overrides));
    }
}
