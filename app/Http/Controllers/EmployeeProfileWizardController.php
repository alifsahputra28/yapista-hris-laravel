<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeContactAddressStepRequest;
use App\Http\Requests\UpdateEmployeeEmergencyContactStepRequest;
use App\Http\Requests\UpdateEmployeeIdentificationStepRequest;
use App\Models\Employee;
use App\Services\EmployeeProfileProgressService;
use App\Support\Profiles\ProfileWizardStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeProfileWizardController extends Controller
{
    public function __construct(private readonly EmployeeProfileProgressService $progressService) {}

    public function index(): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $progress = $this->progressService->calculate($employee);

        return redirect()->route('pegawai.profile.wizard.show', $progress['next_incomplete_step']);
    }

    public function show(string $step): View
    {
        abort_unless(ProfileWizardStep::exists($step), 404);

        $employee = $this->currentEmployee();
        $employee->load(['institution', 'position', 'familyMembers', 'educations', 'certifications', 'administrativeDetail']);

        return view('pegawai.profile.wizard.show', [
            'employee' => $employee,
            'step' => $step,
            'steps' => ProfileWizardStep::all(),
            'previousStep' => ProfileWizardStep::previous($step),
            'nextStep' => ProfileWizardStep::next($step),
            'editable' => $employee->canEditProfile(),
            'profileProgress' => $this->progressService->calculate($employee),
        ]);
    }

    public function updateIdentification(UpdateEmployeeIdentificationStepRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();
        if ($redirect = $this->editLockedRedirect($employee, 'identification')) {
            return $redirect;
        }

        $data = $request->validated();
        $action = $data['wizard_action'];
        unset($data['wizard_action'], $data['photo']);

        if ($request->hasFile('photo')) {
            $newPhoto = $request->file('photo')->store('employees/photos', 'public');
            $oldPhoto = $employee->photo;
            $data['photo'] = $newPhoto;
        }

        $employee->update($data);

        if (isset($oldPhoto) && $oldPhoto !== null) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return $this->savedRedirect('identification', $action);
    }

    public function updateContactAddress(UpdateEmployeeContactAddressStepRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();
        if ($redirect = $this->editLockedRedirect($employee, 'contact-address')) {
            return $redirect;
        }

        $data = $request->validated();
        $action = $data['wizard_action'];
        unset($data['wizard_action']);

        if (($data['domicile_same_as_identity'] ?? false) && filled($data['identity_address'] ?? null)) {
            $data['address'] = $data['identity_address'];
        }

        $employee->update($data);

        return $this->savedRedirect('contact-address', $action);
    }

    public function updateEmergencyContact(UpdateEmployeeEmergencyContactStepRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();
        if ($redirect = $this->editLockedRedirect($employee, 'family')) {
            return $redirect;
        }

        $data = $request->validated();
        $action = $data['wizard_action'];
        unset($data['wizard_action']);
        $employee->update($data);

        return $this->savedRedirect('family', $action);
    }

    private function currentEmployee(): Employee
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee instanceof Employee, 404, 'Data pegawai tidak ditemukan.');

        return $employee;
    }

    private function editLockedRedirect(Employee $employee, string $step): ?RedirectResponse
    {
        if ($employee->canEditProfile()) {
            return null;
        }

        return redirect()
            ->route('pegawai.profile.wizard.show', $step)
            ->with('error', 'Profil sedang terkunci dan tidak dapat diubah.');
    }

    private function savedRedirect(string $step, string $action): RedirectResponse
    {
        $destination = $action === 'next' ? ProfileWizardStep::next($step) : $step;

        return redirect()
            ->route('pegawai.profile.wizard.show', $destination ?? 'review')
            ->with('success', 'Data berhasil disimpan sebagai draft.');
    }
}
