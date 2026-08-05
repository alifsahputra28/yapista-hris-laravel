<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeFamilyMemberRequest;
use App\Http\Requests\UpdateEmployeeFamilyMemberRequest;
use App\Models\Employee;
use App\Models\EmployeeFamilyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeFamilyMemberController extends Controller
{
    public function create(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $familyMember = new EmployeeFamilyMember();

        return view('pegawai.profile.family-members.create', compact('employee', 'familyMember'));
    }

    public function store(StoreEmployeeFamilyMemberRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $employee->familyMembers()->create($request->validated());

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Data anggota keluarga berhasil ditambahkan.');
    }

    public function edit(EmployeeFamilyMember $familyMember): RedirectResponse|View
    {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $familyMember);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        return view('pegawai.profile.family-members.edit', compact('employee', 'familyMember'));
    }

    public function update(
        UpdateEmployeeFamilyMemberRequest $request,
        EmployeeFamilyMember $familyMember,
    ): RedirectResponse {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $familyMember);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $familyMember->update($request->validated());

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Data anggota keluarga berhasil diperbarui.');
    }

    public function destroy(EmployeeFamilyMember $familyMember): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $this->ensureOwnership($employee, $familyMember);

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $familyMember->delete();

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Data anggota keluarga berhasil dihapus.');
    }

    private function currentEmployee(): Employee
    {
        $employee = Auth::user()?->employee;

        abort_unless($employee instanceof Employee, 404, 'Data pegawai tidak ditemukan.');

        return $employee;
    }

    private function ensureOwnership(Employee $employee, EmployeeFamilyMember $familyMember): void
    {
        abort_unless($familyMember->employee_id === $employee->id, 404);
    }

    private function editLockedRedirect(Employee $employee): ?RedirectResponse
    {
        if ($employee->canEditProfile()) {
            return null;
        }

        return redirect()
            ->route('pegawai.profile.show')
            ->with('error', 'Data keluarga tidak dapat diubah saat profil sudah diajukan/diverifikasi.');
    }
}
