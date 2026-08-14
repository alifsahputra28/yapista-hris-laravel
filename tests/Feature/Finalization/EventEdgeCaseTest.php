<?php

namespace Tests\Feature\Finalization;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_validation_rejects_empty_long_invalid_dates_and_reversed_times(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $base = [
            'event_date' => '2026-08-14',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'target_type' => 'all',
        ];

        $cases = [
            [$base + ['name' => ''], 'name'],
            [$base + ['name' => str_repeat('A', 256)], 'name'],
            [array_merge($base, ['name' => 'Tanggal Tidak Valid', 'event_date' => '2026-02-30']), 'event_date'],
            [array_merge($base, ['name' => 'Waktu Terbalik', 'end_time' => '09:59']), 'end_time'],
            [array_merge($base, ['name' => 'Waktu Sama', 'end_time' => '10:00']), 'end_time'],
        ];

        foreach ($cases as [$payload, $field]) {
            $this->actingAs($admin)
                ->post(route('events.store', absolute: false), $payload)
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('events', 0);
    }

    public function test_event_name_boundary_and_past_date_follow_current_rules_without_error(): void
    {
        $admin = User::factory()->create(['role' => 'hr_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('events.store', absolute: false), [
                'name' => str_repeat('A', 255),
                'event_date' => '2020-01-01',
                'start_time' => null,
                'end_time' => null,
                'target_type' => 'all',
            ])
            ->assertRedirect();

        $event = Event::query()->firstOrFail();
        $this->assertSame(255, strlen($event->name));
        $this->assertSame('2020-01-01', $event->event_date->format('Y-m-d'));
        $this->assertSame('draft', $event->status);
    }

    public function test_missing_or_stale_event_mutations_return_not_found_instead_of_server_error(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->put('/events/999999', [
                'name' => 'Stale Event',
                'event_date' => '2026-08-14',
                'target_type' => 'all',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete('/events/999999')
            ->assertNotFound();
    }
}
