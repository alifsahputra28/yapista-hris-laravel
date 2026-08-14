<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeProfileRequest;
use App\Models\Employee;
use App\Services\EmployeePhotoStorageService;
use App\Services\EmployeeProfileProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class EmployeeProfileController extends Controller
{
    public function __construct(
        private readonly EmployeePhotoStorageService $photoStorageService,
    ) {}

    public function show(EmployeeProfileProgressService $progressService): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        $employee->load(['user', 'institution', 'position', 'documents', 'familyMembers', 'educations', 'certifications', 'administrativeDetail']);

        $profileProgress = $progressService->calculate($employee);

        return view('pegawai.profile.show', compact('employee', 'profileProgress'));
    }

    public function edit(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        if (! $employee->canEditProfileCompletion()) {
            return redirect()
                ->route('pegawai.profile.show')
                ->with('error', 'Data sudah diajukan/diverifikasi dan tidak dapat diedit sementara.');
        }

        $employee->load(['institution', 'position']);

        return view('pegawai.profile.edit', compact('employee'));
    }

    public function update(UpdateEmployeeProfileRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        if (! $employee->canEditProfileCompletion()) {
            return redirect()
                ->route('pegawai.profile.show')
                ->with('error', 'Data sudah diajukan/diverifikasi dan tidak dapat diedit sementara.');
        }

        $data = $request->validated();
        unset($data['photo']);

        if (($data['domicile_same_as_identity'] ?? false) && filled($data['identity_address'] ?? null)) {
            $data['address'] = $data['identity_address'];
        }

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

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Profil berhasil disimpan sebagai draft.');
    }

    private function currentEmployee(): ?Employee
    {
        return Auth::user()?->employee;
    }

    private function missingEmployeeRedirect(): RedirectResponse
    {
        return redirect()
            ->route('pegawai.dashboard')
            ->with('error', 'Data pegawai Anda belum terhubung. Silakan hubungi HR/Admin.');
    }
}
