<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeEducationRequest;
use App\Http\Requests\UpdateEmployeeEducationRequest;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeEducationController extends Controller
{
    public function create(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $education = new EmployeeEducation();

        return view('pegawai.profile.educations.create', compact('employee', 'education'));
    }

    public function store(StoreEmployeeEducationRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $data = $request->validated();

        DB::transaction(function () use ($employee, $data): void {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if ($data['is_highest'] ?? false) {
                $employee->educations()->update(['is_highest' => false]);
            }

            $employee->educations()->create($data);
        });

        return redirect()
            ->route('pegawai.profile.wizard.show', 'education')
            ->with('success', 'Data pendidikan berhasil ditambahkan.');
    }

    public function edit(EmployeeEducation $education): RedirectResponse|View
    {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $education);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        return view('pegawai.profile.educations.edit', compact('employee', 'education'));
    }

    public function update(
        UpdateEmployeeEducationRequest $request,
        EmployeeEducation $education,
    ): RedirectResponse {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $education);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $data = $request->validated();

        DB::transaction(function () use ($employee, $education, $data): void {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if ($data['is_highest'] ?? false) {
                $employee->educations()->update(['is_highest' => false]);
            }

            $education->update($data);
        });

        return redirect()
            ->route('pegawai.profile.wizard.show', 'education')
            ->with('success', 'Data pendidikan berhasil diperbarui.');
    }

    public function destroy(EmployeeEducation $education): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $education);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $education->delete();

        return redirect()
            ->route('pegawai.profile.wizard.show', 'education')
            ->with('success', 'Data pendidikan berhasil dihapus.');
    }

    private function currentEmployee(): Employee
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee instanceof Employee, 404, 'Data pegawai tidak ditemukan.');

        return $employee;
    }

    private function ensureOwnership(Employee $employee, EmployeeEducation $education): void
    {
        abort_unless($education->employee_id === $employee->id, 404);
    }

    private function editLockedRedirect(Employee $employee): ?RedirectResponse
    {
        if ($employee->canEditProfile()) {
            return null;
        }

        return redirect()
            ->route('pegawai.profile.show')
            ->with('error', 'Data pendidikan tidak dapat diubah saat profil sudah diajukan/diverifikasi.');
    }
}
