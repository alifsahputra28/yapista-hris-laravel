<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Support\Collection;

class EventParticipantService
{
    /**
     * @param  array<string, mixed>  $targetData
     */
    public function replaceParticipants(Event $event, string $targetType, array $targetData = []): int
    {
        $employeeIds = $this->employeeIdsForTarget($targetType, $targetData);

        $event->participants()->delete();
        $this->insertParticipants($event, $employeeIds);

        return $employeeIds->count();
    }

    /**
     * @param  array<int, int|string>  $employeeIds
     */
    public function addManualParticipants(Event $event, array $employeeIds): int
    {
        $eligibleIds = Employee::query()
            ->eligibleForEvents()
            ->whereIn('id', $employeeIds)
            ->get(['id', 'employee_number'])
            ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber())
            ->map(fn (Employee $employee): int => $employee->id)
            ->unique()
            ->values();

        $added = 0;

        foreach ($eligibleIds as $employeeId) {
            $participant = EventParticipant::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'employee_id' => $employeeId,
                ],
                [
                    'participant_status' => 'invited',
                ]
            );

            if ($participant->wasRecentlyCreated) {
                $added++;
            }
        }

        return $added;
    }

    /**
     * @param  array<string, mixed>  $targetData
     * @return Collection<int, int>
     */
    private function employeeIdsForTarget(string $targetType, array $targetData): Collection
    {
        $query = Employee::query()->eligibleForEvents();

        if ($targetType === 'institution') {
            $query->whereIn('institution_id', $this->cleanIds($targetData['institution_ids'] ?? []));
        }

        if ($targetType === 'position') {
            $query->whereIn('position_id', $this->cleanIds($targetData['position_ids'] ?? []));
        }

        if ($targetType === 'selected') {
            $query->whereIn('id', $this->cleanIds($targetData['employee_ids'] ?? []));
        }

        return $query
            ->get(['id', 'employee_number'])
            ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber())
            ->map(fn (Employee $employee): int => $employee->id)
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, int>  $employeeIds
     */
    private function insertParticipants(Event $event, Collection $employeeIds): void
    {
        if ($employeeIds->isEmpty()) {
            return;
        }

        $now = now();

        EventParticipant::insert($employeeIds->map(fn (int $employeeId): array => [
            'event_id' => $event->id,
            'employee_id' => $employeeId,
            'participant_status' => 'invited',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    /**
     * @param  mixed  $ids
     * @return array<int, int>
     */
    private function cleanIds($ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
