<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeProfileRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployeeProfileController extends Controller
{
    public function show(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        $employee->load(['user', 'institution', 'position', 'documents', 'familyMembers']);

        return view('pegawai.profile.show', compact('employee'));
    }

    public function edit(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        if (! $employee->canEditProfile()) {
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

        if (! $employee->canEditProfile()) {
            return redirect()
                ->route('pegawai.profile.show')
                ->with('error', 'Data sudah diajukan/diverifikasi dan tidak dapat diedit sementara.');
        }

        $data = $request->validated();
        unset($data['photo']);

        if (($data['domicile_same_as_identity'] ?? false) && filled($data['identity_address'] ?? null)) {
            $data['address'] = $data['identity_address'];
        }

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }

            $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
        }

        $employee->update($data);

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Profil berhasil disimpan sebagai draft.');
    }

    public function submit(): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        $employee->load('documents');

        if (! $employee->canEditProfile()) {
            return redirect()
                ->route('pegawai.profile.show')
                ->with('error', 'Data sudah diajukan/diverifikasi dan tidak dapat diajukan ulang sementara.');
        }

        $missing = [];

        if (! $employee->hasRequiredProfileData()) {
            $missing[] = 'lengkapi nama, NIK, nomor HP, alamat, dan foto profil';
        }

        if (! $employee->hasRequiredDocuments()) {
            $missing[] = 'upload dokumen KTP';
        }

        if ($missing !== []) {
            return redirect()
                ->route('pegawai.profile.show')
                ->with('error', 'Belum bisa diajukan: '.implode(', ', $missing).'.');
        }

        $employee->update([
            'verification_status' => 'submitted',
            'verification_note' => null,
        ]);

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Biodata berhasil diajukan untuk verifikasi HR.');
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
