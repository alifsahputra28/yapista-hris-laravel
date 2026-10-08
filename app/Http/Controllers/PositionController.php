<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Position;
use App\Support\OrganizationOptions;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $institutionId = $request->integer('institution_id');
        $type = OrganizationOptions::normalizePositionType($request->string('type')->toString());
        $perPage = in_array($request->integer('per_page'), [15, 25, 50], true)
            ? $request->integer('per_page')
            : 15;

        $positions = Position::query()
            ->with('institution')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('institution', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('level', 'like', "%{$search}%");
                        });
                });
            })
            ->when($institutionId > 0, fn ($query) => $query->where('institution_id', $institutionId))
            ->when($type, fn ($query) => $query->whereIn('type', OrganizationOptions::positionTypeDatabaseValues($type)))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $totalPositions = Position::query()->count();
        $activePositions = Position::query()->where('status', 'active')->count();
        $inactivePositions = Position::query()->where('status', 'inactive')->count();
        $totalInstitutions = Institution::query()->count();
        $institutions = $this->institutionOptions();
        $positionTypes = OrganizationOptions::POSITION_TYPES;

        return view('positions.index', compact(
            'positions',
            'search',
            'totalPositions',
            'activePositions',
            'inactivePositions',
            'totalInstitutions',
            'institutions',
            'institutionId',
            'perPage',
            'type',
            'positionTypes'
        ));
    }

    public function create(): View
    {
        $position = new Position([
            'status' => 'active',
        ]);
        $institutions = $this->institutionOptions();

        return view('positions.create', [
            'position' => $position,
            'institutions' => $institutions,
            'positionTypes' => OrganizationOptions::POSITION_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            Position::create($this->validatedData($request));
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Nama jabatan sudah digunakan pada unit kerja tersebut.']);
        }

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Position $position): View
    {
        $institutions = $this->institutionOptions();

        return view('positions.edit', [
            'position' => $position,
            'institutions' => $institutions,
            'positionTypes' => OrganizationOptions::POSITION_TYPES,
        ]);
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        try {
            $position->update($this->validatedData($request, $position));
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Nama jabatan sudah digunakan pada unit kerja tersebut.']);
        }

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->employees()->exists()) {
            return redirect()
                ->route('positions.index')
                ->with('error', 'Jabatan tidak dapat dihapus karena masih digunakan oleh pegawai.');
        }

        try {
            $position->delete();
        } catch (QueryException) {
            return redirect()
                ->route('positions.index')
                ->with('error', 'Jabatan tidak dapat dihapus karena masih digunakan oleh data lain.');
        }

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Position $position = null): array
    {
        $request->merge(['type' => strtoupper(trim((string) $request->input('type')))]);
        $nameRule = Rule::unique('positions', 'name')
            ->where(fn ($query) => $query->where('institution_id', $request->integer('institution_id')));

        if ($position) {
            $nameRule->ignore($position->id);
        }

        return $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $position): void {
                    $normalized = mb_strtolower(trim((string) $value));
                    $duplicate = Position::query()
                        ->where('institution_id', $request->integer('institution_id'))
                        ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->when($position, fn ($query) => $query->whereKeyNot($position->id))
                        ->exists();

                    if ($duplicate) {
                        $fail('Nama jabatan sudah digunakan pada unit kerja tersebut.');
                    }
                },
                $nameRule,
            ],
            'type' => ['required', Rule::in(OrganizationOptions::positionTypeCodes())],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    private function institutionOptions()
    {
        return Institution::query()
            ->orderBy('name')
            ->get();
    }
}
