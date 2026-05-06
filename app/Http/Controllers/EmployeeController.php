<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $employees = Employee::query()
            ->with(['institution', 'position'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('institution_id'), function ($query) use ($request): void {
                $query->where('institution_id', $request->integer('institution_id'));
            })
            ->when($request->filled('position_id'), function ($query) use ($request): void {
                $query->where('position_id', $request->integer('position_id'));
            })
            ->when($request->filled('verification_status'), function ($query) use ($request): void {
                $query->where('verification_status', $request->string('verification_status')->toString());
            })
            ->when($request->filled('employment_status'), function ($query) use ($request): void {
                $query->where('employment_status', $request->string('employment_status')->toString());
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $institutions = Institution::query()->orderBy('name')->get();
        $positions = Position::query()->with('institution')->orderBy('name')->get();

        return view('employees.index', compact('employees', 'institutions', 'positions', 'search'));
    }

    public function create(): View
    {
        $employee = new Employee([
            'employment_status' => 'aktif',
            'verification_status' => 'draft',
        ]);
        $institutions = $this->activeInstitutions();
        $positions = $this->activePositions();

        return view('employees.create', compact('employee', 'institutions', 'positions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['verification_status'] = 'draft';

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
        }

        Employee::create($data);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function show(Employee $employee): View
    {
        $employee->load(['user', 'institution', 'position', 'verifier', 'documents']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $institutions = $this->activeInstitutions();
        $positions = $this->activePositions();

        return view('employees.edit', compact('employee', 'institutions', 'positions'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $this->validatedData($request, $employee);

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }

            $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
        }

        $employee->update($data);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->update([
            'employment_status' => 'nonaktif',
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Pegawai berhasil dinonaktifkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Employee $employee = null): array
    {
        $emailRule = Rule::unique('employees', 'email');
        $nikRule = Rule::unique('employees', 'nik');

        if ($employee) {
            $emailRule->ignore($employee->id);
            $nikRule->ignore($employee->id);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'institution_id' => ['required', 'exists:institutions,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'email' => ['nullable', 'email', $emailRule],
            'nik' => ['nullable', 'string', 'max:30', $nikRule],
            'gender' => ['nullable', 'in:male,female'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'employee_type' => ['required', 'string', 'max:100'],
            'employment_status' => ['required', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($data['photo']);

        return $data;
    }

    private function activeInstitutions()
    {
        return Institution::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function activePositions()
    {
        return Position::query()
            ->with('institution')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
