<?php

namespace App\Http\Controllers;

use App\Exports\EmployeesReportExport;
use App\Exports\EventAttendancesReportExport;
use App\Exports\EventsReportExport;
use App\Models\Employee;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Services\EventAttendanceSummaryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly EventAttendanceSummaryService $attendanceSummaryService) {}

    private const EMPLOYEE_TYPES = [
        'guru' => 'Guru',
        'dosen' => 'Dosen',
        'tenaga_kependidikan' => 'Tenaga Kependidikan',
        'staff_yayasan' => 'Staff Yayasan',
        'security' => 'Security',
        'cleaning_service' => 'Cleaning Service',
        'driver' => 'Driver',
        'teknisi' => 'Teknisi',
    ];

    private const EMPLOYMENT_STATUSES = [
        'aktif' => 'Aktif',
        'kontrak' => 'Kontrak',
        'honorer' => 'Honorer',
        'part_time' => 'Part Time',
        'nonaktif' => 'Nonaktif',
        'resign' => 'Resign',
    ];

    private const VERIFICATION_STATUSES = [
        'draft' => 'Draft',
        'submitted' => 'Menunggu Verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ];

    public function employees(Request $request): View
    {
        $employees = $this->employeesQuery($request)
            ->paginate(20)
            ->withQueryString();

        $institutions = Institution::query()->orderBy('name')->get();
        $positions = Position::query()->with('institution')->orderBy('name')->get();
        $summary = [
            'totalEmployees' => Employee::query()->count(),
            'activeEmployees' => Employee::query()->where('employment_status', 'aktif')->count(),
            'registeredEmployees' => Employee::query()->whereNotNull('user_id')->count(),
            'verifiedEmployees' => Employee::query()->where('verification_status', 'verified')->count(),
        ];

        return view('reports.employees', [
            'employees' => $employees,
            'institutions' => $institutions,
            'positions' => $positions,
            'employeeTypes' => self::EMPLOYEE_TYPES,
            'employmentStatuses' => self::EMPLOYMENT_STATUSES,
            'verificationStatuses' => self::VERIFICATION_STATUSES,
        ] + $summary);
    }

    public function exportEmployees(Request $request): StreamedResponse
    {
        return (new EmployeesReportExport($this->employeesQuery($request)))
            ->download('laporan-pegawai-'.now()->format('Ymd').'.xlsx');
    }

    public function events(Request $request): View
    {
        $events = $this->eventsQuery($request)
            ->paginate(20)
            ->withQueryString();

        $summaryEvents = $this->attendanceSummaryService
            ->withActiveCounts(Event::query())
            ->get();
        $averageAttendance = $summaryEvents->count() > 0
            ? round($summaryEvents->avg(function (Event $event): float {
                return (float) $this->attendanceSummaryService
                    ->summarizeLoaded($event)['attendancePercentage'];
            }), 1)
            : 0;

        return view('reports.events', [
            'events' => $events,
            'targetTypes' => Event::TARGET_TYPES,
            'eventStatuses' => Event::STATUSES,
            'totalEvents' => Event::query()->count(),
            'activeEvents' => Event::query()->where('status', 'active')->count(),
            'closedEvents' => Event::query()->where('status', 'closed')->count(),
            'averageAttendance' => $averageAttendance,
        ]);
    }

    public function exportEvents(Request $request): StreamedResponse
    {
        return (new EventsReportExport(
            $this->eventsQuery($request),
            $this->attendanceSummaryService,
        ))
            ->download('laporan-kegiatan-'.now()->format('Ymd').'.xlsx');
    }

    public function eventAttendances(Request $request, Event $event): View
    {
        $participants = $this->eventParticipantsQuery($request, $event)
            ->paginate(20)
            ->withQueryString();
        $attendanceMap = $this->attendanceSummaryService->attendanceMap($event);
        $institutions = Institution::query()->orderBy('name')->get();
        $positions = Position::query()->with('institution')->orderBy('name')->get();
        $summary = $this->attendanceSummaryService->summarize($event);

        $event->load('creator');

        return view('reports.event-attendances', [
            'event' => $event,
            'participants' => $participants,
            'attendanceMap' => $attendanceMap,
            'institutions' => $institutions,
            'positions' => $positions,
        ] + $summary);
    }

    public function exportEventAttendances(Request $request, Event $event): StreamedResponse
    {
        $filename = 'laporan-kehadiran-'.$this->safeFilenameSegment($event->name).'-'.now()->format('Ymd').'.xlsx';

        return (new EventAttendancesReportExport(
            $event,
            $this->eventParticipantsQuery($request, $event),
            $this->attendanceSummaryService->attendanceMap($event)
        ))->download($filename);
    }

    /**
     * @return Builder<Employee>
     */
    private function employeesQuery(Request $request): Builder
    {
        $search = trim($request->string('search')->toString());
        $employeeType = $request->string('employee_type')->toString();
        $employmentStatus = $request->string('employment_status')->toString();
        $verificationStatus = $request->string('verification_status')->toString();
        $registrationStatus = $request->string('registration_status')->toString();
        $employeeNumberStatus = $request->string('employee_number_status')->toString();
        $validEmployeeNumberIds = null;

        if (in_array($employeeNumberStatus, ['filled', 'empty'], true)) {
            $validEmployeeNumberIds = Employee::query()
                ->get(['id', 'employee_number'])
                ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber())
                ->pluck('id');
        }

        return Employee::query()
            ->with(['institution', 'position', 'user'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('institution_id'), function (Builder $query) use ($request): void {
                $query->where('institution_id', $request->input('institution_id'));
            })
            ->when($request->filled('position_id'), function (Builder $query) use ($request): void {
                $query->where('position_id', $request->input('position_id'));
            })
            ->when(array_key_exists($employeeType, self::EMPLOYEE_TYPES), function (Builder $query) use ($employeeType): void {
                $query->where('employee_type', $employeeType);
            })
            ->when(array_key_exists($employmentStatus, self::EMPLOYMENT_STATUSES), function (Builder $query) use ($employmentStatus): void {
                $query->where('employment_status', $employmentStatus);
            })
            ->when(array_key_exists($verificationStatus, self::VERIFICATION_STATUSES), function (Builder $query) use ($verificationStatus): void {
                $query->where('verification_status', $verificationStatus);
            })
            ->when($registrationStatus === 'registered', function (Builder $query): void {
                $query->whereNotNull('user_id');
            })
            ->when($registrationStatus === 'unregistered', function (Builder $query): void {
                $query->whereNull('user_id');
            })
            ->when($employeeNumberStatus === 'filled', function (Builder $query) use ($validEmployeeNumberIds): void {
                $query->whereIn('id', $validEmployeeNumberIds ?? []);
            })
            ->when($employeeNumberStatus === 'empty', function (Builder $query) use ($validEmployeeNumberIds): void {
                $query->whereNotIn('id', $validEmployeeNumberIds ?? []);
            })
            ->orderBy('full_name');
    }

    /**
     * @return Builder<Event>
     */
    private function eventsQuery(Request $request): Builder
    {
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $targetType = $request->string('target_type')->toString();

        return $this->attendanceSummaryService
            ->withActiveCounts(Event::query()->with('creator'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, Event::STATUSES), function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when(array_key_exists($targetType, Event::TARGET_TYPES), function (Builder $query) use ($targetType): void {
                $query->where('target_type', $targetType);
            })
            ->when($request->filled('date_from'), function (Builder $query) use ($request): void {
                $query->whereDate('event_date', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function (Builder $query) use ($request): void {
                $query->whereDate('event_date', '<=', $request->input('date_to'));
            })
            ->orderByDesc('event_date')
            ->latest();
    }

    /**
     * @return Builder<EventParticipant>
     */
    private function eventParticipantsQuery(Request $request, Event $event): Builder
    {
        $search = trim($request->string('search')->toString());
        $attendanceStatus = $request->string('attendance_status')->toString();
        $scanMethod = $request->string('scan_method')->toString();

        return EventParticipant::query()
            ->where('event_id', $event->id)
            ->where('participant_status', '!=', 'cancelled')
            ->with(['employee.institution', 'employee.position'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('employee', function (Builder $query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('institution_id'), function (Builder $query) use ($request): void {
                $query->whereHas('employee', function (Builder $query) use ($request): void {
                    $query->where('institution_id', $request->input('institution_id'));
                });
            })
            ->when($request->filled('position_id'), function (Builder $query) use ($request): void {
                $query->whereHas('employee', function (Builder $query) use ($request): void {
                    $query->where('position_id', $request->input('position_id'));
                });
            })
            ->when($attendanceStatus === 'present', function (Builder $query) use ($event): void {
                $query->whereHas('employee.eventAttendances', function (Builder $query) use ($event): void {
                    $query->where('event_id', $event->id);
                });
            })
            ->when($attendanceStatus === 'absent', function (Builder $query) use ($event): void {
                $query->whereDoesntHave('employee.eventAttendances', function (Builder $query) use ($event): void {
                    $query->where('event_id', $event->id);
                });
            })
            ->when(in_array($scanMethod, ['barcode', 'manual'], true), function (Builder $query) use ($event, $scanMethod): void {
                $query->whereHas('employee.eventAttendances', function (Builder $query) use ($event, $scanMethod): void {
                    $query->where('event_id', $event->id)
                        ->where('scan_method', $scanMethod);
                });
            })
            ->orderBy('id');
    }

    private function safeFilenameSegment(string $value): string
    {
        return Str::slug($value) ?: 'kegiatan';
    }
}
