<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Services\EventParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventParticipantController extends Controller
{
    public function __construct(private readonly EventParticipantService $participantService) {}

    public function index(Request $request, Event $event): View
    {
        $search = trim($request->string('search')->toString());

        $participants = EventParticipant::query()
            ->where('event_id', $event->id)
            ->with(['employee.institution', 'employee.position'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('employee', function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('participant_status'), function ($query) use ($request): void {
                $query->where('participant_status', $request->string('participant_status')->toString());
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
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $event->loadCount('participants');
        $eligibleEmployees = $this->employeeOptions();
        $institutions = Institution::query()->orderBy('name')->get();
        $positions = Position::query()->orderBy('name')->get();

        return view('events.participants', compact(
            'event',
            'participants',
            'eligibleEmployees',
            'institutions',
            'positions',
            'search'
        ));
    }

    public function generate(Request $request, Event $event): RedirectResponse
    {
        if (! $event->canGenerateParticipants()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Peserta hanya bisa digenerate saat kegiatan masih draft.');
        }

        $data = $this->validatedTargetData($request);

        $participantCount = DB::transaction(function () use ($data, $event): int {
            $event->update(['target_type' => $data['target_type']]);

            return $this->participantService->replaceParticipants($event, $data['target_type'], $data);
        });

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Peserta kegiatan berhasil digenerate ulang. '.$participantCount.' peserta dibuat.');
    }

    public function storeManual(Request $request, Event $event): RedirectResponse
    {
        if (! $event->isDraft()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Peserta manual hanya bisa ditambahkan saat kegiatan masih draft.');
        }

        $validated = $request->validate([
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        $added = $this->participantService->addManualParticipants($event, $validated['employee_ids']);

        return redirect()
            ->route('events.show', $event)
            ->with('success', $added.' peserta manual berhasil ditambahkan.');
    }

    public function destroy(EventParticipant $participant): RedirectResponse
    {
        $participant->load('event');
        $event = $participant->event;

        if (! $event?->isDraft()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Peserta hanya bisa dihapus saat kegiatan masih draft.');
        }

        $participant->delete();

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Peserta berhasil dihapus dari kegiatan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTargetData(Request $request): array
    {
        $targetType = $request->input('target_type');

        return $request->validate([
            'target_type' => ['required', Rule::in(array_keys(Event::TARGET_TYPES))],
            'institution_ids' => [Rule::requiredIf($targetType === 'institution'), 'array'],
            'institution_ids.*' => ['integer', 'exists:institutions,id'],
            'position_ids' => [Rule::requiredIf($targetType === 'position'), 'array'],
            'position_ids.*' => ['integer', 'exists:positions,id'],
            'employee_ids' => [Rule::requiredIf($targetType === 'selected'), 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);
    }

    private function employeeOptions()
    {
        return Employee::query()
            ->eligibleForEvents()
            ->withValidEmployeeNumber()
            ->with(['institution', 'position'])
            ->orderBy('full_name')
            ->get()
            ->values();
    }
}
