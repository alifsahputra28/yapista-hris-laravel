<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Services\EmployeeQrTokenService;
use App\Services\EventAttendanceService;
use App\Services\EventAttendanceSummaryService;
use App\Support\Attendances\AttendanceResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventAttendanceController extends Controller
{
    public function __construct(
        private readonly EventAttendanceService $attendanceService,
        private readonly EmployeeQrTokenService $qrTokenService,
        private readonly EventAttendanceSummaryService $attendanceSummaryService,
    ) {}

    public function index(Request $request, Event $event): View
    {
        $event->load('creator');

        $search = $request->string('search')->toString();
        $attendanceStatus = $request->string('attendance_status')->toString();
        $scanMethod = $request->string('scan_method')->toString();

        $participantsQuery = EventParticipant::query()
            ->where('event_id', $event->id)
            ->where('participant_status', '!=', 'cancelled')
            ->with(['employee.institution', 'employee.position'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('employee', function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('institution_id'), function ($query) use ($request): void {
                $query->whereHas('employee', function ($query) use ($request): void {
                    $query->where('institution_id', $request->integer('institution_id'));
                });
            })
            ->when($request->filled('position_id'), function ($query) use ($request): void {
                $query->whereHas('employee', function ($query) use ($request): void {
                    $query->where('position_id', $request->integer('position_id'));
                });
            })
            ->when($attendanceStatus === 'hadir', function ($query) use ($event): void {
                $query->whereHas('employee.eventAttendances', function ($query) use ($event): void {
                    $query->where('event_id', $event->id);
                });
            })
            ->when($attendanceStatus === 'belum_hadir', function ($query) use ($event): void {
                $query->whereDoesntHave('employee.eventAttendances', function ($query) use ($event): void {
                    $query->where('event_id', $event->id);
                });
            })
            ->when(in_array($scanMethod, ['qr', 'manual', 'barcode'], true), function ($query) use ($event, $scanMethod): void {
                $query->whereHas('employee.eventAttendances', function ($query) use ($event, $scanMethod): void {
                    $query->where('event_id', $event->id)
                        ->where('scan_method', $scanMethod);
                });
            })
            ->orderBy('id');

        $participants = $participantsQuery
            ->paginate(20)
            ->withQueryString();

        $activeParticipantEmployeeIds = $this->attendanceSummaryService->activeParticipantEmployeeIds($event);
        $attendanceMap = $this->attendanceSummaryService->attendanceMap($event, $participants->pluck('employee_id'));
        $summary = $this->attendanceSummaryService->summarize($event, $activeParticipantEmployeeIds);

        $institutions = Institution::query()->orderBy('name')->get();
        $positions = Position::query()->orderBy('name')->get();
        $manualEmployees = $this->manualEmployeeOptions($event);

        return view('event-attendances.index', array_merge(compact(
            'event',
            'participants',
            'attendanceMap',
            'institutions',
            'positions',
            'manualEmployees',
            'search',
            'attendanceStatus'
        ), $summary));
    }

    public function scanner(Event $event): View|RedirectResponse
    {
        if ($message = $this->attendanceService->inactiveEventMessage($event)) {
            return redirect()
                ->route('events.attendances.index', $event)
                ->with('error', $message);
        }

        $event->load('creator');

        $summary = $this->attendanceSummaryService->summarize($event);
        $manualEmployees = $this->manualEmployeeOptions($event);

        return view('event-attendances.scanner', array_merge(compact(
            'event',
            'manualEmployees'
        ), $summary));
    }

    public function scan(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $scanInput = $request->input('qr_payload', $request->input('payload'));

        if (blank($scanInput)) {
            return $this->scanResponse($request, false, 'QR Code wajib dipindai.');
        }

        if ($message = $this->attendanceService->inactiveEventMessage($event)) {
            return $this->scanResponse($request, false, $message);
        }

        $qrToken = $this->qrTokenService->resolvePayload((string) $scanInput);

        if (! $qrToken?->employee) {
            return $this->scanResponse($request, false, 'QR Code tidak valid atau sudah tidak aktif.');
        }

        $employee = $qrToken->employee->loadMissing(['institution', 'position']);

        $result = $this->attendanceService->recordQrAttendance(
            $event,
            $employee,
            $request->user(),
            $qrToken,
        );

        return $this->attendanceResultResponse($request, $result, $employee);
    }

    public function manual(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::query()
            ->with(['institution', 'position'])
            ->findOrFail($validated['employee_id']);

        $result = $this->attendanceService->recordManualAttendance(
            $event,
            $employee,
            $request->user(),
            $validated['note'] ?? null,
        );

        return back()->with($this->flashKey($result), $result->message);
    }

    public function destroy(EventAttendance $attendance): RedirectResponse
    {
        $attendance->load('event');

        if ($attendance->event?->isClosed()) {
            return back()->with('error', 'Absensi tidak dapat dihapus karena kegiatan sudah ditutup.');
        }

        $attendance->delete();

        return back()->with('success', 'Data attendance berhasil dihapus.');
    }

    private function attendanceResultResponse(
        Request $request,
        AttendanceResult $result,
        Employee $employee,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result->isSuccess(),
                'status' => $result->status,
                'message' => $result->message,
                'employee' => [
                    'full_name' => $employee->full_name,
                    'employee_number' => $employee->employee_number,
                    'institution' => $employee->institution?->name,
                    'position' => $employee->position?->name,
                    'scanned_at' => $result->attendance?->scanned_at?->format('d M Y H:i:s'),
                ],
            ], $result->isSuccess() ? 200 : ($result->isAlreadyAttended() ? 409 : 422));
        }

        return back()->with($this->flashKey($result), $result->message);
    }

    private function flashKey(AttendanceResult $result): string
    {
        return $result->isSuccess()
            ? 'success'
            : ($result->isAlreadyAttended() ? 'warning' : 'error');
    }

    private function scanResponse(
        Request $request,
        bool $success,
        string $message,
        ?Employee $employee = null,
        ?EventAttendance $attendance = null
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'employee' => $employee ? [
                    'full_name' => $employee->full_name,
                    'employee_number' => $employee->employee_number,
                    'institution' => $employee->institution?->name,
                    'position' => $employee->position?->name,
                    'scanned_at' => $attendance?->scanned_at?->format('d M Y H:i:s'),
                ] : null,
            ], $success ? 200 : 422);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }

    private function manualEmployeeOptions(Event $event)
    {
        return Employee::query()
            ->eligibleForEvents()
            ->withValidEmployeeNumber()
            ->whereHas('eventParticipants', function ($query) use ($event): void {
                $query->where('event_id', $event->id)
                    ->where('participant_status', '!=', 'cancelled');
            })
            ->whereDoesntHave('eventAttendances', function ($query) use ($event): void {
                $query->where('event_id', $event->id);
            })
            ->with(['institution', 'position'])
            ->orderBy('full_name')
            ->get()
            ->values();
    }
}
