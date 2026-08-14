<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Rules\UniqueEmployeeNik;
use App\Services\EmployeeMetricsService;
use App\Services\EmployeeNikProtectionService;
use App\Services\EmployeePhotoStorageService;
use App\Services\EmployeeQrTokenService;
use App\Support\Imports\EmployeeImportColumns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeNikProtectionService $nikProtectionService,
        private readonly EmployeeMetricsService $employeeMetricsService,
        private readonly EmployeeQrTokenService $qrTokenService,
        private readonly EmployeePhotoStorageService $photoStorageService,
    ) {}

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
            ->when($request->filled('employee_type'), function ($query) use ($request): void {
                $query->where('employee_type', $request->string('employee_type')->toString());
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $institutions = Institution::query()->orderBy('name')->get();
        $positions = Position::query()->orderBy('name')->get();
        $metrics = $this->employeeMetricsService->counts();
        $totalEmployees = $metrics['total'];
        $activeEmployees = $metrics['active'];
        $submittedEmployees = $metrics['submitted'];
        $registeredEmployees = $metrics['registered'];
        $importRequiredColumns = EmployeeImportColumns::requiredLabels();
        $importOptionalColumns = EmployeeImportColumns::optionalLabels();

        return view('employees.index', compact(
            'employees',
            'institutions',
            'positions',
            'search',
            'totalEmployees',
            'activeEmployees',
            'submittedEmployees',
            'registeredEmployees',
            'importRequiredColumns',
            'importOptionalColumns'
        ));
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
        $hasEmployeeNumber = filled($data['employee_number'] ?? null);
        $data['verification_status'] = $hasEmployeeNumber ? 'verified' : 'draft';
        $data['verified_by'] = $hasEmployeeNumber ? $request->user()?->id : null;
        $data['verified_at'] = $hasEmployeeNumber ? now() : null;

        $newPhoto = $request->hasFile('photo')
            ? $this->photoStorageService->store($request->file('photo'))
            : null;

        if ($newPhoto !== null) {
            $data['photo'] = $newPhoto;
        }

        try {
            DB::transaction(function () use ($data, $request): void {
                $employee = Employee::create($data);

                if ($employee->isEligibleForIdCard()) {
                    $this->qrTokenService->generate($employee, $request->user());
                }
            });
        } catch (Throwable $exception) {
            $this->photoStorageService->deletePath($newPhoto);

            throw $exception;
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function show(Employee $employee): View
    {
        $employee->load(['user', 'institution', 'position', 'verifier', 'documents']);

        return view('employees.show', compact('employee'));
    }

    public function findByNik(Request $request): RedirectResponse
    {
        try {
            $lookup = $this->nikProtectionService->lookup((string) $request->input('nik'));
        } catch (InvalidArgumentException) {
            return back()->with('error', 'NIK harus terdiri dari 16 digit angka.');
        }

        $employee = $lookup === null ? null : Employee::where('nik_lookup', $lookup)->first();

        if (! $employee) {
            return back()->with('error', 'Data pegawai dengan NIK tersebut tidak ditemukan.');
        }

        return redirect()->route('employees.show', $employee);
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

        if (! $employee->isVerified() && filled($data['employee_number'] ?? null)) {
            $data['verification_status'] = 'verified';
            $data['verified_by'] = $request->user()?->id;
            $data['verified_at'] = now();
        }

        $oldPhoto = $employee->photo;
        $newPhoto = $request->hasFile('photo')
            ? $this->photoStorageService->store($request->file('photo'))
            : null;

        if ($newPhoto !== null) {
            $data['photo'] = $newPhoto;
        }

        try {
            DB::transaction(function () use ($employee, $data, $request): void {
                $employee->update($data);

                if ($employee->isEligibleForIdCard()) {
                    $this->qrTokenService->generate($employee, $request->user());
                } elseif ($employee->activeQrToken()->exists()) {
                    $this->qrTokenService->revoke($employee);
                }
            });
        } catch (Throwable $exception) {
            $this->photoStorageService->deletePath($newPhoto);

            throw $exception;
        }

        if ($newPhoto !== null) {
            $this->photoStorageService->deletePath($oldPhoto);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($employee): void {
            $employee->update([
                'employment_status' => 'nonaktif',
            ]);

            if ($employee->activeQrToken()->exists()) {
                $this->qrTokenService->revoke($employee);
            }
        });

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
        $employeeNumberRule = Rule::unique('employees', 'employee_number');

        if ($employee) {
            $emailRule->ignore($employee->id);
            $employeeNumberRule->ignore($employee->id);
        }

        $employeeNumberRules = [
            $employee?->isVerified() ? 'required' : 'nullable',
            'digits:'.Employee::EMPLOYEE_NUMBER_LENGTH,
            $employeeNumberRule,
        ];

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'institution_id' => ['required', 'exists:institutions,id'],
            'position_id' => [
                'required',
                Rule::exists('positions', 'id')->where(
                    fn ($query) => $query->where('institution_id', $request->integer('institution_id'))
                ),
            ],
            'employee_number' => $employeeNumberRules,
            'email' => ['nullable', 'email', $emailRule],
            'nik' => ['nullable', 'digits:16', new UniqueEmployeeNik($employee?->id)],
            'gender' => ['nullable', 'in:male,female'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'employee_type' => ['required', Rule::in(array_keys(EmployeeImportColumns::EMPLOYEE_TYPES))],
            'employment_status' => ['required', Rule::in(array_keys(EmployeeImportColumns::EMPLOYMENT_STATUSES))],
            'join_date' => ['nullable', 'date'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
            ],
        ], [
            'employee_number.required' => 'NUP / Nomor Pegawai wajib diisi untuk pegawai yang sudah terverifikasi.',
            'employee_number.digits' => 'NUP / Nomor Pegawai harus terdiri dari 10 digit angka.',
            'employee_number.unique' => 'NUP / Nomor Pegawai sudah digunakan oleh pegawai lain.',
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
