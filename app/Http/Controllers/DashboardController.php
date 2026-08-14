<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Services\DashboardMetricsService;
use App\Services\EmployeeQrTokenService;
use App\Support\IdCards\QrCodeRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetricsService $dashboardMetricsService): View
    {
        return view('dashboard', [
            'dashboard' => $dashboardMetricsService->adminDashboard(),
        ]);
    }

    public function employee(
        Request $request,
        EmployeeQrTokenService $tokenService,
        QrCodeRenderer $qrCodeRenderer,
    ): View {
        $employee = $request->user()->employee?->load(['institution', 'position', 'activeQrToken']);
        $nextEvent = null;
        $recentAttendances = collect();
        $qrCodeSvg = null;

        if ($employee) {
            if ($employee->activeQrToken?->isActive()) {
                try {
                    $qrCodeSvg = $qrCodeRenderer->render($tokenService->payloadFor($employee->activeQrToken));
                } catch (Throwable) {
                    $qrCodeSvg = null;
                }
            }

            $nextEvent = Event::query()
                ->where('status', 'active')
                ->whereDate('event_date', '>=', today())
                ->whereHas('participants', function ($query) use ($employee): void {
                    $query->where('employee_id', $employee->id)
                        ->where('participant_status', '!=', 'cancelled');
                })
                ->with(['participants' => function ($query) use ($employee): void {
                    $query->where('employee_id', $employee->id);
                }])
                ->orderBy('event_date')
                ->orderBy('start_time')
                ->first();

            $recentAttendances = EventAttendance::query()
                ->where('employee_id', $employee->id)
                ->with('event')
                ->latest('scanned_at')
                ->limit(3)
                ->get();
        }

        return view('pegawai.dashboard', compact('employee', 'nextEvent', 'recentAttendances', 'qrCodeSvg'));
    }

    public function panitia(): View
    {
        $activeEvents = Event::query()
            ->where('status', 'active')
            ->withCount(['participants', 'attendances'])
            ->orderBy('event_date')
            ->get();

        return view('scanner.dashboard', compact('activeEvents'));
    }
}
