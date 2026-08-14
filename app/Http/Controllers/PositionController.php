<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Position;
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
            ->orderBy('name')
            ->get();

        $totalPositions = Position::query()->count();
        $activePositions = Position::query()->where('status', 'active')->count();
        $inactivePositions = Position::query()->where('status', 'inactive')->count();
        $totalInstitutions = Institution::query()->count();

        return view('positions.index', compact(
            'positions',
            'search',
            'totalPositions',
            'activePositions',
            'inactivePositions',
            'totalInstitutions'
        ));
    }

    public function create(): View
    {
        $position = new Position([
            'status' => 'active',
        ]);
        $institutions = $this->institutionOptions();

        return view('positions.create', compact('position', 'institutions'));
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

        return view('positions.edit', compact('position', 'institutions'));
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
            'type' => ['nullable', Rule::in(['struktural', 'fungsional', 'administratif', 'teknis'])],
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
