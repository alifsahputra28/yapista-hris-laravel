<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EventAttendanceSummaryService
{
    /**
     * Add portable correlated counts for active participants and their attendances.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function withActiveCounts(Builder $query): Builder
    {
        return $query->addSelect([
            'active_participants_count' => EventParticipant::query()
                ->selectRaw('count(*)')
                ->whereColumn('event_participants.event_id', 'events.id')
                ->where('event_participants.participant_status', '!=', 'cancelled'),
            'active_attendances_count' => EventAttendance::query()
                ->selectRaw('count(*)')
                ->join('event_participants', function ($join): void {
                    $join->on('event_participants.event_id', '=', 'event_attendances.event_id')
                        ->on('event_participants.employee_id', '=', 'event_attendances.employee_id');
                })
                ->whereColumn('event_attendances.event_id', 'events.id')
                ->where('event_participants.participant_status', '!=', 'cancelled'),
        ]);
    }

    /**
     * @return array{totalParticipants: int, attendedCount: int, absentCount: int, attendancePercentage: float|int}
     */
    public function summarize(Event $event): array
    {
        $activeParticipantEmployeeIds = $this->activeParticipantEmployeeIds($event);

        return $this->fromCounts(
            $activeParticipantEmployeeIds->count(),
            EventAttendance::query()
                ->where('event_id', $event->id)
                ->whereIn('employee_id', $activeParticipantEmployeeIds)
                ->count(),
        );
    }

    /**
     * Use preloaded report counts without issuing queries per event.
     *
     * @return array{totalParticipants: int, attendedCount: int, absentCount: int, attendancePercentage: float|int}
     */
    public function summarizeLoaded(Event $event): array
    {
        return $this->fromCounts(
            (int) $event->getAttribute('active_participants_count'),
            (int) $event->getAttribute('active_attendances_count'),
        );
    }

    /**
     * @return Collection<int, int>
     */
    public function activeParticipantEmployeeIds(Event $event): Collection
    {
        return EventParticipant::query()
            ->where('event_id', $event->id)
            ->where('participant_status', '!=', 'cancelled')
            ->pluck('employee_id');
    }

    /**
     * @return Collection<int, EventAttendance>
     */
    public function attendanceMap(Event $event): Collection
    {
        return EventAttendance::query()
            ->where('event_id', $event->id)
            ->whereIn('employee_id', $this->activeParticipantEmployeeIds($event))
            ->with(['scanner', 'employee'])
            ->get()
            ->keyBy('employee_id');
    }

    /**
     * @return array{totalParticipants: int, attendedCount: int, absentCount: int, attendancePercentage: float|int}
     */
    private function fromCounts(int $totalParticipants, int $attendedCount): array
    {
        $attendedCount = min($attendedCount, $totalParticipants);
        $absentCount = max($totalParticipants - $attendedCount, 0);
        $attendancePercentage = $totalParticipants > 0
            ? round(($attendedCount / $totalParticipants) * 100, 1)
            : 0;

        return compact('totalParticipants', 'attendedCount', 'absentCount', 'attendancePercentage');
    }
}
