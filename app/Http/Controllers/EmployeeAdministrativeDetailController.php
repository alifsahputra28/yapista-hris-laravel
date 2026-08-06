<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeAdministrativeDetailRequest;
use App\Models\Employee;
use App\Models\EmployeeAdministrativeDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeAdministrativeDetailController extends Controller
{
    public function edit(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $administrativeDetail = $employee->administrativeDetail ?? new EmployeeAdministrativeDetail();

        return view('pegawai.profile.administrative-details.edit', compact('employee', 'administrativeDetail'));
    }

    public function update(UpdateEmployeeAdministrativeDetailRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if ($redirect = $this->editLockedRedirect($employee)) {
            return $redirect;
        }

        $data = $request->validated();
        $wizardAction = $data['wizard_action'] ?? null;
        unset($data['wizard_action']);
        $employee->administrativeDetail()->updateOrCreate([], $data);

        if ($wizardAction !== null) {
            return redirect()
                ->route('pegawai.profile.wizard.show', $wizardAction === 'next' ? 'review' : 'administration')
                ->with('success', 'Data berhasil disimpan sebagai draft.');
        }

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Data bank, pajak, dan BPJS berhasil disimpan.');
    }

    private function currentEmployee(): Employee
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee instanceof Employee, 404, 'Data pegawai tidak ditemukan.');

        return $employee;
    }

    private function editLockedRedirect(Employee $employee): ?RedirectResponse
    {
        if ($employee->canEditProfileCompletion()) {
            return null;
        }

        return redirect()
            ->route('pegawai.profile.show')
            ->with('error', 'Data administrasi tidak dapat diubah saat profil sudah diajukan/diverifikasi.');
    }
}
