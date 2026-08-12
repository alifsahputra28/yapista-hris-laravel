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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSelfServiceUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_home_prioritizes_upcoming_event_and_recent_attendance(): void
    {
        [$user, $employee] = $this->employeeUser();
        $event = Event::create([
            'name' => 'Rapat Koordinasi Pegawai',
            'event_date' => today()->addDay(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'location' => 'Aula YAPISTA',
            'target_type' => 'selected',
            'created_by' => $user->id,
            'status' => 'active',
        ]);
        EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'participant_status' => 'confirmed',
        ]);
        EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'scanned_by' => $user->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => 'manual',
        ]);

        $response = $this->actingAs($user)
            ->get(route('pegawai.dashboard', absolute: false))
            ->assertOk()
            ->assertSee('Kegiatan Terdekat')
            ->assertSee('Rapat Koordinasi Pegawai')
            ->assertSee('Kehadiran Terakhir')
            ->assertSee('Beranda')
            ->assertSee('Kegiatan')
            ->assertSee('ID Card')
            ->assertSee('Dokumen')
            ->assertSee('Akun')
            ->assertSee('employee-mobile-appbar', escape: false)
            ->assertSee('employee-bottom-nav', escape: false)
            ->assertDontSee('Profil Saya');
    }

    public function test_verified_employee_account_shows_employment_context_once_and_optional_progress(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)
            ->get(route('pegawai.profile.show', absolute: false))
            ->assertOk()
            ->assertSee('Informasi Kepegawaian')
            ->assertSee('Data tambahan')
            ->assertSee('Opsional')
            ->assertSee('Keamanan Akun')
            ->assertSee('Informasi Kepegawaian')
            ->assertDontSee('Profil belum lengkap');
    }

    public function test_employee_activity_pages_only_show_their_own_events(): void
    {
        [$user, $employee] = $this->employeeUser();
        $event = Event::create([
            'name' => 'Kegiatan Pegawai Sendiri',
            'event_date' => today()->addDays(2),
            'start_time' => '09:00',
            'location' => 'Aula',
            'target_type' => 'selected',
            'created_by' => $user->id,
            'status' => 'active',
        ]);
        EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'participant_status' => 'invited',
        ]);
        $otherEvent = Event::create([
            'name' => 'Kegiatan Pegawai Lain',
            'event_date' => today()->addDays(3),
            'start_time' => '10:00',
            'location' => 'Ruang Rapat',
            'target_type' => 'selected',
            'created_by' => $user->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('pegawai.activities.index', absolute: false))
            ->assertOk()
            ->assertSee('Akan Datang')
            ->assertSee('Riwayat')
            ->assertSee('Kegiatan Pegawai Sendiri')
            ->assertDontSee('Kegiatan Pegawai Lain');

        $this->actingAs($user)
            ->get(route('pegawai.activities.show', $event, absolute: false))
            ->assertOk()
            ->assertSee('Kegiatan Pegawai Sendiri');

        $this->actingAs($user)
            ->get(route('pegawai.activities.show', $otherEvent, absolute: false))
            ->assertNotFound();

        auth()->logout();
        $this->get(route('pegawai.activities.index', absolute: false))
            ->assertRedirect(route('login', absolute: false));

        $panitia = User::factory()->create(['role' => 'panitia', 'status' => 'active']);
        $this->actingAs($panitia)
            ->get(route('pegawai.activities.index', absolute: false))
            ->assertForbidden();
    }

    public function test_employee_documents_use_compact_list_instead_of_admin_table(): void
    {
        [$user, $employee] = $this->employeeUser();
        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'document_type' => 'ktp',
            'file_path' => 'employee-documents/1/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'file_size' => 1024,
            'status' => 'valid',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('pegawai.documents.index', absolute: false))
            ->assertOk()
            ->assertSee('Dokumen Tersimpan')
            ->assertSee('ktp.pdf')
            ->assertSee('list-group', escape: false)
            ->assertDontSee('<table', escape: false);
    }

    public function test_employee_security_page_uses_mantis_layout(): void
    {
        [$user] = $this->employeeUser();

        $this->actingAs($user)
            ->get(route('profile.edit', absolute: false))
            ->assertOk()
            ->assertSee('Keamanan Akun')
            ->assertSee('Informasi Login')
            ->assertSee('Ubah Password')
            ->assertSee('pc-sidebar', escape: false)
            ->assertDontSee('py-12', escape: false);
    }

    /**
     * @return array{User, Employee}
     */
    private function employeeUser(): array
    {
        $institution = Institution::create([
            'name' => 'Unit Employee Self Service',
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => 'Pegawai',
            'type' => 'administratif',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'name' => 'Ahmad Fauzi',
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'employee_number' => '7770990001',
            'full_name' => 'Ahmad Fauzi',
            'email' => $user->email,
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        return [$user, $employee];
    }
}
