<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeCertificationRequest;
use App\Http\Requests\UpdateEmployeeCertificationRequest;
use App\Models\Employee;
use App\Models\EmployeeCertification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeCertificationController extends Controller
{
    public function create(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $certification = new EmployeeCertification();

        return view('pegawai.profile.certifications.create', compact('employee', 'certification'));
    }

    public function store(StoreEmployeeCertificationRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $employee->certifications()->create($request->validated());

        return redirect()
            ->route('pegawai.profile.wizard.show', 'education')
            ->with('success', 'Data sertifikasi berhasil ditambahkan.');
    }

    public function edit(EmployeeCertification $certification): RedirectResponse|View
    {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $certification);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        return view('pegawai.profile.certifications.edit', compact('employee', 'certification'));
    }

    public function update(
        UpdateEmployeeCertificationRequest $request,
        EmployeeCertification $certification,
    ): RedirectResponse {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $certification);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $certification->update($request->validated());

        return redirect()
            ->route('pegawai.profile.wizard.show', 'education')
            ->with('success', 'Data sertifikasi berhasil diperbarui.');
    }

    public function destroy(EmployeeCertification $certification): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $certification);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $certification->delete();

        return redirect()
            ->route('pegawai.profile.wizard.show', 'education')
            ->with('success', 'Data sertifikasi berhasil dihapus.');
    }

    private function currentEmployee(): Employee
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee instanceof Employee, 404, 'Data pegawai tidak ditemukan.');

        return $employee;
    }

    private function ensureOwnership(Employee $employee, EmployeeCertification $certification): void
    {
        abort_unless($certification->employee_id === $employee->id, 404);
    }

    private function editLockedRedirect(Employee $employee): ?RedirectResponse
    {
        if ($employee->canEditProfile()) {
            return null;
        }

        return redirect()
            ->route('pegawai.profile.show')
            ->with('error', 'Data sertifikasi tidak dapat diubah saat profil sudah diajukan/diverifikasi.');
    }
}
