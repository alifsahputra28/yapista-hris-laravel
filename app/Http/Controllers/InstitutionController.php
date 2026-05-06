<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

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
            ->orderBy('name')
            ->get();

        return view('institutions.index', compact('institutions', 'search'));
    }

    public function create(): View
    {
        $institution = new Institution([
            'status' => 'active',
        ]);

        return view('institutions.create', compact('institution'));
    }

    public function store(Request $request): RedirectResponse
    {
        Institution::create($this->validatedData($request));

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Unit kerja berhasil ditambahkan.');
    }

    public function edit(Institution $institution): View
    {
        return view('institutions.edit', compact('institution'));
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $institution->update($this->validatedData($request));

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Unit kerja berhasil diperbarui.');
    }

    public function destroy(Institution $institution): RedirectResponse
    {
        $institution->delete();

        return redirect()
            ->route('institutions.index')
            ->with('success', 'Unit kerja berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
