<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeContactAddressStepRequest;
use App\Http\Requests\UpdateEmployeeEmergencyContactStepRequest;
use App\Http\Requests\UpdateEmployeeIdentificationStepRequest;
use App\Models\Employee;
use App\Services\EmployeePhotoStorageService;
use App\Services\EmployeeProfileProgressService;
use App\Services\EmployeeProfileSubmissionService;
use App\Support\Profiles\ProfileWizardStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class EmployeeProfileWizardController extends Controller
{
    public function __construct(
        private readonly EmployeeProfileProgressService $progressService,
        private readonly EmployeeProfileSubmissionService $submissionService,
        private readonly EmployeePhotoStorageService $photoStorageService,
    ) {}

    public function index(): RedirectResponse
    {
        $employee = $this->currentEmployee();
        $progress = $this->progressService->calculate($employee);
        $nextStep = $progress['next_incomplete_step'];

        if ($employee->isVerified()) {
            $nextStep = collect(ProfileWizardStep::EXISTING_EMPLOYEE_STEPS)
                ->first(fn (string $step): bool => $step === 'review'
                    || ! ($progress['sections'][$step]['completed'] ?? false));
        }

        return redirect()->route('pegawai.profile.wizard.show', $nextStep ?? 'review');
    }

    public function show(string $step): View
    {
        $employee = $this->currentEmployee();
        $steps = ProfileWizardStep::for($employee);
        abort_unless(array_key_exists($step, $steps), 404);

        $relations = ['institution', 'position', 'familyMembers', 'educations', 'administrativeDetail'];
        if (in_array($step, ['education', 'review'], true)) {
            $relations[] = 'certifications';
        }
        $employee->load($relations);
        $submissionChecklist = $step === 'review' && ! $employee->isVerified()
            ? $this->submissionService->inspect($employee)
            : null;

        return view('pegawai.profile.wizard.show', [
            'employee' => $employee,
            'step' => $step,
            'steps' => $steps,
            'previousStep' => ProfileWizardStep::previousIn($step, $steps),
            'nextStep' => ProfileWizardStep::nextIn($step, $steps),
            'editable' => $employee->canEditProfileCompletion(),
            'profileProgress' => $this->progressService->calculate($employee),
            'submissionChecklist' => $submissionChecklist,
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

        $oldPhoto = $employee->photo;
        $newPhoto = $request->hasFile('photo')
            ? $this->photoStorageService->store($request->file('photo'))
            : null;

        if ($newPhoto !== null) {
            $data['photo'] = $newPhoto;
        }

        try {
            $employee->update($data);
        } catch (Throwable $exception) {
            $this->photoStorageService->deletePath($newPhoto);

            throw $exception;
        }

        if ($newPhoto !== null) {
            $this->photoStorageService->deletePath($oldPhoto);
        }

        return $this->savedRedirect($employee, 'identification', $action);
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

        return $this->savedRedirect($employee, 'contact-address', $action);
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

        return $this->savedRedirect($employee, 'family', $action);
    }

    private function currentEmployee(): Employee
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee instanceof Employee, 404, 'Data pegawai tidak ditemukan.');

        return $employee;
    }

    private function editLockedRedirect(Employee $employee, string $step): ?RedirectResponse
    {
        if ($employee->canEditProfileCompletion()) {
            return null;
        }

        return redirect()
            ->route('pegawai.profile.wizard.show', $step)
            ->with('error', 'Profil sedang terkunci dan tidak dapat diubah.');
    }

    private function savedRedirect(Employee $employee, string $step, string $action): RedirectResponse
    {
        $steps = ProfileWizardStep::for($employee);
        $destination = $action === 'next' ? ProfileWizardStep::nextIn($step, $steps) : $step;

        return redirect()
            ->route('pegawai.profile.wizard.show', $destination ?? 'review')
            ->with('success', $employee->isVerified()
                ? 'Data profil berhasil disimpan.'
                : 'Data berhasil disimpan sebagai draft.');
    }
}
