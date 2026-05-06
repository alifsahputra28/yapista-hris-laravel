<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeProfileController extends Controller
{
    public function show(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        $employee->load(['user', 'institution', 'position', 'documents']);

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

        return view('pegawai.profile.edit', compact('employee'));
    }

    public function update(Request $request): RedirectResponse
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

        $data = $this->validatedData($request, $employee);

        if ($request->hasFile('photo')) {
            if ($employee->photo) {
                Storage::disk('public')->delete($employee->photo);
            }

            $data['photo'] = $request->file('photo')->store('employees/photos', 'public');
        }

        if ($employee->isRejected()) {
            $data['verification_status'] = 'draft';
            $data['verification_note'] = null;
        }

        $employee->update($data);

        return redirect()
            ->route('pegawai.profile.show')
            ->with('success', 'Biodata berhasil diperbarui.');
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

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, Employee $employee): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:30', Rule::unique('employees', 'nik')->ignore($employee->id)],
            'gender' => ['nullable', 'in:male,female'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($data['photo']);

        return $data;
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
