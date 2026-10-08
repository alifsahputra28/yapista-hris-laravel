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

class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $level = OrganizationOptions::normalizeUnitLevel($request->string('level')->toString());
        $perPage = in_array($request->integer('per_page'), [15, 25, 50], true)
            ? $request->integer('per_page')
            : 15;

        $institutions = Institution::query()
            ->withCount('positions')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('level', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->when($level, fn ($query) => $query->whereIn('level', OrganizationOptions::unitLevelDatabaseValues($level)))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $totalInstitutions = Institution::query()->count();
        $activeInstitutions = Institution::query()->where('status', 'active')->count();
        $inactiveInstitutions = Institution::query()->where('status', 'inactive')->count();
        $totalPositions = Position::query()->count();
        $unitLevels = OrganizationOptions::UNIT_LEVELS;

        return view('institutions.index', compact(
            'institutions',
            'search',
            'totalInstitutions',
            'activeInstitutions',
            'inactiveInstitutions',
            'totalPositions',
            'perPage',
            'level',
            'unitLevels'
        ));
    }

    public function create(): View
    {
        $institution = new Institution([
            'status' => 'active',
        ]);

        return view('institutions.create', [
            'institution' => $institution,
            'unitLevels' => OrganizationOptions::UNIT_LEVELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            Institution::create($this->validatedData($request));
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Nama unit kerja sudah digunakan.']);
        }

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Unit kerja berhasil ditambahkan.');
    }

    public function edit(Institution $institution): View
    {
        return view('institutions.edit', [
            'institution' => $institution,
            'unitLevels' => OrganizationOptions::UNIT_LEVELS,
        ]);
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        try {
            $institution->update($this->validatedData($request, $institution));
        } catch (UniqueConstraintViolationException) {
            return back()
                ->withInput()
                ->withErrors(['name' => 'Nama unit kerja sudah digunakan.']);
        }

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Unit kerja berhasil diperbarui.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        if ($institution->employees()->exists() || $institution->positions()->exists()) {
            return redirect()
                ->route('institutions.index')
                ->with('error', 'Unit kerja tidak dapat dihapus karena masih digunakan oleh jabatan atau pegawai.');
        }

        try {
            $institution->delete();
        } catch (QueryException) {
            return redirect()
                ->route('institutions.index')
                ->with('error', 'Unit kerja tidak dapat dihapus karena masih digunakan oleh data lain.');
        }

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Unit kerja berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Institution $institution = null): array
    {
        $request->merge(['level' => strtoupper(trim((string) $request->input('level')))]);
        $nameRule = Rule::unique('institutions', 'name');

        if ($institution) {
            $nameRule->ignore($institution->id);
        }

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($institution): void {
                    $normalized = mb_strtolower(trim((string) $value));
                    $duplicate = Institution::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [$normalized])
                        ->when($institution, fn ($query) => $query->whereKeyNot($institution->id))
                        ->exists();

                    if ($duplicate) {
                        $fail('Nama unit kerja sudah digunakan.');
                    }
                },
                $nameRule,
            ],
            'level' => ['required', Rule::in(OrganizationOptions::unitLevelCodes())],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

}
