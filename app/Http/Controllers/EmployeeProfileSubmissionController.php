<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitEmployeeProfileRequest;
use App\Models\Employee;
use App\Services\EmployeeProfileSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeProfileSubmissionController extends Controller
{
    public function store(
        SubmitEmployeeProfileRequest $request,
        EmployeeProfileSubmissionService $submissionService,
    ): RedirectResponse {
        $employee = Auth::user()?->employee;
        abort_unless($employee instanceof Employee, 404, 'Data pegawai tidak ditemukan.');

        $result = $submissionService->submit($employee, $request->user());

        if ($result['already_submitted']) {
            return redirect()
                ->route('pegawai.profile.wizard.show', 'review')
                ->with('warning', 'Profil sudah dikirim dan sedang menunggu pemeriksaan.');
        }

        if (! $result['submitted']) {
            return redirect()
                ->route('pegawai.profile.wizard.show', 'review')
                ->with('error', 'Profil belum dapat dikirim. Lengkapi data dan dokumen yang masih kurang.');
        }

        return redirect()
            ->route('pegawai.profile.wizard.show', 'review')
            ->with('success', 'Profil berhasil dikirim untuk diperiksa oleh HR/Admin.');
    }
}
