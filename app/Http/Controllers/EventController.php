<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Position;
use App\Services\EventParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(private readonly EventParticipantService $participantService) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $events = Event::query()
            ->with('creator')
            ->withCount('participants')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('target_type'), function ($query) use ($request): void {
                $query->where('target_type', $request->string('target_type')->toString());
            })
            ->when($request->filled('date_from'), function ($query) use ($request): void {
                $query->whereDate('event_date', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request): void {
                $query->whereDate('event_date', '<=', $request->input('date_to'));
            })
            ->orderByDesc('event_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('events.index', compact('events', 'search'));
    }

    public function create(): View
    {
        $event = new Event([
            'target_type' => 'all',
            'status' => 'draft',
        ]);
        [$institutions, $positions, $employees] = $this->formOptions();

        return view('events.create', compact('event', 'institutions', 'positions', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        [$event, $participantCount] = DB::transaction(function () use ($data): array {
            $event = Event::create($this->eventPayload($data) + [
                'created_by' => Auth::id(),
                'status' => 'draft',
            ]);

            $participantCount = $this->participantService->replaceParticipants($event, $data['target_type'], $data);

            return [$event, $participantCount];
        });

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Kegiatan berhasil ditambahkan. '.$participantCount.' peserta dibuat.');
    }

    public function show(Event $event): View
    {
        $event->load([
            'creator',
            'participants' => fn ($query) => $query
                ->with(['employee.institution', 'employee.position'])
                ->orderBy('id'),
        ]);

        $participantCounts = $event->participants->countBy('participant_status');
        $eligibleEmployees = $this->employeeOptions();
        $institutions = Institution::query()->where('status', 'active')->orderBy('name')->get();
        $positions = Position::query()->with('institution')->where('status', 'active')->orderBy('name')->get();

        return view('events.show', compact('event', 'participantCounts', 'eligibleEmployees', 'institutions', 'positions'));
    }

    public function edit(Event $event): View|RedirectResponse
    {
        if (! $event->canBeEdited()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Kegiatan hanya bisa diedit saat status draft.');
        }

        [$institutions, $positions, $employees] = $this->formOptions();

        return view('events.edit', compact('event', 'institutions', 'positions', 'employees'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        if (! $event->canBeEdited()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Kegiatan hanya bisa diperbarui saat status draft.');
        }

        $data = $this->validatedData($request, $event);
        $originalTargetType = $event->target_type;

        $participantCount = DB::transaction(function () use ($data, $event, $originalTargetType, $request): int {
            $event->update($this->eventPayload($data));

            if ($originalTargetType !== $data['target_type'] || $request->boolean('regenerate_participants')) {
                return $this->participantService->replaceParticipants($event, $data['target_type'], $data);
            }

            return $event->participants()->count();
        });

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Kegiatan berhasil diperbarui. Total peserta saat ini: '.$participantCount.'.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        if (! $event->isDraft() && ! $event->isCancelled()) {
            return redirect()
                ->route('events.index')
                ->with('error', 'Kegiatan aktif atau tertutup tidak bisa dihapus.');
        }

        $event->delete();

        return redirect()
            ->route('events.index')
            ->with('success', 'Kegiatan berhasil dihapus.');
    }

    public function activate(Event $event): RedirectResponse
    {
        if (! $event->isDraft()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Hanya kegiatan draft yang bisa diaktifkan.');
        }

        if ($event->participants()->count() === 0) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Kegiatan harus memiliki minimal 1 peserta sebelum diaktifkan.');
        }

        $event->update(['status' => 'active']);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Kegiatan berhasil diaktifkan.');
    }

    public function close(Event $event): RedirectResponse
    {
        if (! $event->isActive()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Hanya kegiatan aktif yang bisa ditutup.');
        }

        $event->update(['status' => 'closed']);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Kegiatan berhasil ditutup.');
    }

    public function cancel(Event $event): RedirectResponse
    {
        if ($event->isClosed()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Kegiatan yang sudah ditutup tidak bisa dibatalkan.');
        }

        if (! $event->isDraft() && ! $event->isActive()) {
            return redirect()
                ->route('events.show', $event)
                ->with('error', 'Hanya kegiatan draft atau aktif yang bisa dibatalkan.');
        }

        $event->update(['status' => 'cancelled']);

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Kegiatan berhasil dibatalkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Event $event = null): array
    {
        $targetType = $request->input('target_type');
        $shouldGenerate = $event === null
            || $event->target_type !== $targetType
            || $request->boolean('regenerate_participants');
        $endTimeRules = ['nullable', 'date_format:H:i'];

        if ($request->filled('start_time')) {
            $endTimeRules[] = 'after:start_time';
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => $endTimeRules,
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_type' => ['required', Rule::in(array_keys(Event::TARGET_TYPES))],
            'institution_ids' => [Rule::requiredIf($targetType === 'institution' && $shouldGenerate), 'array'],
            'institution_ids.*' => ['integer', 'exists:institutions,id'],
            'position_ids' => [Rule::requiredIf($targetType === 'position' && $shouldGenerate), 'array'],
            'position_ids.*' => ['integer', 'exists:positions,id'],
            'employee_ids' => [Rule::requiredIf($targetType === 'selected' && $shouldGenerate), 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'regenerate_participants' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function eventPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'event_date' => $data['event_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'location' => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
            'target_type' => $data['target_type'],
        ];
    }

    private function formOptions(): array
    {
        return [
            Institution::query()->where('status', 'active')->orderBy('name')->get(),
            Position::query()->with('institution')->where('status', 'active')->orderBy('name')->get(),
            $this->employeeOptions(),
        ];
    }

    private function employeeOptions()
    {
        return Employee::query()
            ->eligibleForEvents()
            ->with(['institution', 'position'])
            ->orderBy('full_name')
            ->get();
    }
}
