<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeActivityController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $upcomingParticipants = EventParticipant::query()
            ->where('employee_id', $employee->id)
            ->where('participant_status', '!=', 'cancelled')
            ->whereHas('event', fn ($query) => $query
                ->where('status', 'active')
                ->whereDate('event_date', '>=', today()))
            ->with('event')
            ->get()
            ->sortBy(fn (EventParticipant $participant) => sprintf(
                '%s %s',
                $participant->event?->event_date?->format('Y-m-d') ?? '9999-12-31',
                $participant->event?->start_time?->format('H:i') ?? '23:59',
            ))
            ->values();

        $attendanceHistory = EventAttendance::query()
            ->where('employee_id', $employee->id)
            ->with('event')
            ->latest('scanned_at')
            ->get();

        return view('pegawai.activities.index', compact('employee', 'upcomingParticipants', 'attendanceHistory'));
    }

    public function show(Request $request, Event $event): View
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $participant = $event->participants()
            ->where('employee_id', $employee->id)
            ->where('participant_status', '!=', 'cancelled')
            ->firstOrFail();
        $attendance = $event->attendances()
            ->where('employee_id', $employee->id)
            ->first();

        return view('pegawai.activities.show', compact('event', 'participant', 'attendance'));
    }
}
