<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('positions.index', compact('positions', 'search'));
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
        Position::create($this->validatedData($request));

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
        $position->update($this->validatedData($request));

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $position->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'institution_id' => ['required', 'exists:institutions,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
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
