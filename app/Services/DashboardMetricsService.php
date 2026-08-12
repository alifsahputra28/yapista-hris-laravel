<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Event;
use App\Models\Institution;
use Illuminate\Support\Str;

class DashboardMetricsService
{
    private const EMPLOYEE_TYPE_LABELS = [
        'guru' => 'Guru',
        'dosen' => 'Dosen',
        'tenaga_kependidikan' => 'Tenaga Kependidikan',
        'staff_yayasan' => 'Staff Yayasan',
        'security' => 'Security',
        'cleaning_service' => 'Cleaning Service',
        'driver' => 'Driver',
        'teknisi' => 'Teknisi',
    ];

    public function __construct(private readonly EventAttendanceSummaryService $attendanceSummaryService) {}

    /**
     * @return array<string, mixed>
     */
    public function adminDashboard(): array
    {
        $attendanceTrend = $this->attendanceTrend();

        return [
            'kpis' => [
                'totalEmployees' => Employee::query()->count(),
                'activeEmployees' => Employee::query()->where('employment_status', 'aktif')->count(),
                'eventsThisMonth' => Event::query()
                    ->whereBetween('event_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                    ->count(),
                'averageAttendance' => $attendanceTrend['percentages'] === []
                    ? 0
                    : round(collect($attendanceTrend['percentages'])->avg(), 1),
            ],
            'institutionDistribution' => $this->institutionDistribution(),
            'employeeComposition' => $this->employeeComposition(),
            'attendanceTrend' => $attendanceTrend,
            'insights' => [
                'submittedEmployees' => Employee::query()->where('verification_status', 'submitted')->count(),
                'rejectedDocuments' => EmployeeDocument::query()->where('status', 'rejected')->count(),
                'rejectedProfiles' => Employee::query()
                    ->where('profile_review_status', Employee::PROFILE_REVIEW_REJECTED)
                    ->count(),
                'activeEventsToday' => Event::query()
                    ->where('status', 'active')
                    ->whereDate('event_date', now()->toDateString())
                    ->count(),
            ],
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function institutionDistribution(): array
    {
        $institutions = Institution::query()
            ->withCount([
                'employees as active_employees_count' => fn ($query) => $query->where('employment_status', 'aktif'),
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (Institution $institution): bool => (int) $institution->active_employees_count > 0)
            ->sortByDesc(fn (Institution $institution): int => (int) $institution->active_employees_count)
            ->values();

        return [
            'labels' => $institutions->pluck('name')->values()->all(),
            'values' => $institutions->pluck('active_employees_count')->map(fn ($count): int => (int) $count)->values()->all(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function employeeComposition(): array
    {
        $composition = Employee::query()
            ->where('employment_status', 'aktif')
            ->select('employee_type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('employee_type')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $composition
                ->map(fn (Employee $employee): string => self::EMPLOYEE_TYPE_LABELS[$employee->employee_type]
                    ?? Str::of((string) $employee->employee_type)->replace('_', ' ')->title()->toString())
                ->values()
                ->all(),
            'values' => $composition->pluck('total')->map(fn ($count): int => (int) $count)->values()->all(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, percentages: array<int, float|int>, attended: array<int, int>, participants: array<int, int>}
     */
    private function attendanceTrend(): array
    {
        $events = $this->attendanceSummaryService
            ->withActiveCounts(
                Event::query()
                    ->whereIn('status', ['active', 'closed'])
                    ->whereDate('event_date', '<=', now()->toDateString())
                    ->whereHas('participants', fn ($query) => $query->where('participant_status', '!=', 'cancelled'))
            )
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $summaries = $events->map(fn (Event $event): array => $this->attendanceSummaryService->summarizeLoaded($event));

        return [
            'labels' => $events->map(fn (Event $event): string => Str::limit($event->name, 28))->all(),
            'percentages' => $summaries->pluck('attendancePercentage')->all(),
            'attended' => $summaries->pluck('attendedCount')->map(fn ($count): int => (int) $count)->all(),
            'participants' => $summaries->pluck('totalParticipants')->map(fn ($count): int => (int) $count)->all(),
        ];
    }
}
