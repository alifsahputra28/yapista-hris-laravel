<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_event_generate_participants_and_manage_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        [$institutionA, $positionA] = $this->institutionAndPosition('SMK Ibnu Sina', 'Guru');
        [$institutionB, $positionB] = $this->institutionAndPosition('Kantor Yayasan', 'Staff Yayasan');

        $eligibleA = $this->employee($institutionA, $positionA, [
            'full_name' => 'Ahmad Fauzi',
            'employee_number' => '777.0526.0001',
        ]);
        $secondEligibleA = $this->employee($institutionA, $positionA, [
            'full_name' => 'Siti Aminah',
            'employee_number' => '777.0526.0002',
        ]);
        $eligibleB = $this->employee($institutionB, $positionB, [
            'full_name' => 'Budi Santoso',
            'employee_number' => '777.0526.0003',
        ]);
        $this->employee($institutionA, $positionA, [
            'full_name' => 'Belum Verified',
            'employee_number' => '777.0526.0004',
            'verification_status' => 'submitted',
        ]);
        $this->employee($institutionA, $positionA, [
            'full_name' => 'Belum Nomor',
            'employee_number' => null,
        ]);
        $this->employee($institutionA, $positionA, [
            'full_name' => 'Pegawai Nonaktif',
            'employee_number' => '777.0526.0005',
            'employment_status' => 'nonaktif',
        ]);

        $this->actingAs($admin)
            ->get('/events')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/events', [
                'name' => 'Rapat Koordinasi Yayasan',
                'event_date' => '2026-06-01',
                'start_time' => '08:00',
                'end_time' => '10:00',
                'location' => 'Aula YAPISTA',
                'description' => 'Koordinasi rutin.',
                'target_type' => 'institution',
                'institution_ids' => [$institutionA->id],
            ])
            ->assertRedirect();

        $event = Event::where('name', 'Rapat Koordinasi Yayasan')->firstOrFail();

        $this->assertSame('draft', $event->status);
        $this->assertSame('institution', $event->target_type);
        $this->assertSame($admin->id, $event->created_by);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'employee_id' => $eligibleA->id,
            'participant_status' => 'invited',
        ]);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'employee_id' => $secondEligibleA->id,
            'participant_status' => 'invited',
        ]);
        $this->assertSame(2, EventParticipant::where('event_id', $event->id)->count());

        $this->actingAs($admin)
            ->get(route('events.show', $event, absolute: false))
            ->assertOk()
            ->assertSee('Rapat Koordinasi Yayasan')
            ->assertSee('Ahmad Fauzi');

        $this->actingAs($admin)
            ->post(route('events.participants.manual', $event, absolute: false), [
                'employee_ids' => [$eligibleB->id, $eligibleA->id],
            ])
            ->assertRedirect(route('events.show', $event, absolute: false));

        $this->assertSame(3, EventParticipant::where('event_id', $event->id)->count());

        $participant = EventParticipant::where('event_id', $event->id)
            ->where('employee_id', $eligibleB->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('event-participants.destroy', $participant, absolute: false))
            ->assertRedirect(route('events.show', $event, absolute: false));

        $this->assertDatabaseMissing('event_participants', ['id' => $participant->id]);

        $this->actingAs($admin)
            ->post(route('events.activate', $event, absolute: false))
            ->assertRedirect(route('events.show', $event, absolute: false));

        $this->assertSame('active', $event->refresh()->status);

        $this->actingAs($admin)
            ->post(route('events.close', $event, absolute: false))
            ->assertRedirect(route('events.show', $event, absolute: false));

        $this->assertSame('closed', $event->refresh()->status);

        $this->actingAs($admin)
            ->post(route('events.cancel', $event, absolute: false))
            ->assertSessionHas('error', 'Kegiatan yang sudah ditutup tidak bisa dibatalkan.');
    }

    public function test_admin_can_regenerate_draft_event_participants(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        [$institution, $position] = $this->institutionAndPosition('SMK Ibnu Sina', 'Guru');
        $first = $this->employee($institution, $position, [
            'employee_number' => '777.0526.0101',
        ]);
        $second = $this->employee($institution, $position, [
            'employee_number' => '777.0526.0102',
        ]);
        $event = Event::create([
            'name' => 'Workshop Guru',
            'event_date' => '2026-06-02',
            'target_type' => 'selected',
            'created_by' => $admin->id,
            'status' => 'draft',
        ]);
        EventParticipant::create([
            'event_id' => $event->id,
            'employee_id' => $first->id,
        ]);

        $this->actingAs($admin)
            ->post(route('events.participants.generate', $event, absolute: false), [
                'target_type' => 'selected',
                'employee_ids' => [$second->id],
            ])
            ->assertRedirect(route('events.show', $event, absolute: false));

        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $event->id,
            'employee_id' => $first->id,
        ]);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'employee_id' => $second->id,
        ]);
    }

    public function test_non_admin_roles_can_not_access_event_management(): void
    {
        foreach (['panitia', 'pegawai'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get('/events')
                ->assertForbidden();
        }
    }

    /**
     * @return array{Institution, Position}
     */
    private function institutionAndPosition(string $institutionName, string $positionName): array
    {
        $institution = Institution::create([
            'name' => $institutionName,
            'level' => 'Unit',
            'status' => 'active',
        ]);
        $position = Position::create([
            'institution_id' => $institution->id,
            'name' => $positionName,
            'type' => 'fungsional',
            'status' => 'active',
        ]);

        return [$institution, $position];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function employee(Institution $institution, Position $position, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'institution_id' => $institution->id,
            'position_id' => $position->id,
            'full_name' => 'Pegawai '.uniqid(),
            'email' => uniqid('pegawai').'@yapista.test',
            'employee_number' => '777.0526.'.str_pad((string) random_int(100, 999), 4, '0', STR_PAD_LEFT),
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ], $overrides));
    }
}
