<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Services\EventParticipantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventParticipantController extends Controller
{
    public function __construct(private readonly EventParticipantService $participantService) {}

    public function index(Event $event): View
    {
        $event->load([
            'participants' => fn ($query) => $query
                ->with(['employee.institution', 'employee.position'])
                ->orderBy('id'),
        ]);
        $eligibleEmployees = $this->employeeOptions();

        return view('events.participants', compact('event', 'eligibleEmployees'));
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
            ->with(['institution', 'position'])
            ->orderBy('full_name')
            ->get();
    }
}
