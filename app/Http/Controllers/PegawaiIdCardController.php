<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Services\EmployeeQrTokenService;
use App\Support\IdCards\QrCodeRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class PegawaiIdCardController extends Controller
{
    public function __construct(
        private readonly EmployeeQrTokenService $tokenService,
        private readonly QrCodeRenderer $qrCodeRenderer,
    ) {}

    public function show(): View
    {
        $employee = auth()->user()?->employee?->load(['institution', 'position', 'activeQrToken']);
        $qrCodeSvg = $employee ? $this->qrCodeSvg($employee->activeQrToken) : null;
        $warnings = $employee
            ? $this->warnings($employee)
            : ['Data pegawai belum terhubung dengan akun Anda.'];

        if ($employee && $this->isValidForIdCard($employee) && ! $employee->activeQrToken) {
            $warnings[] = 'QR Code belum tersedia. Silakan hubungi HR/Admin.';
        } elseif ($employee?->activeQrToken && $qrCodeSvg === null) {
            $warnings[] = 'QR Code tidak dapat ditampilkan. Silakan hubungi HR/Admin.';
        }

        return view('pegawai.id-card.show', [
            'employee' => $employee,
            'qrCodeSvg' => $qrCodeSvg,
            'warnings' => $warnings,
            'isValidForIdCard' => $employee ? $this->isValidForIdCard($employee) : false,
            'isReadyForIdCard' => $employee
                ? $this->isValidForIdCard($employee) && $qrCodeSvg !== null
                : false,
        ]);
    }

    public function download(): RedirectResponse
    {
        return redirect()
            ->route('pegawai.id-card.show')
            ->with('error', 'Download PDF ID Card belum tersedia. Silakan gunakan print dari browser.');
    }

    /**
     * @return array<int, string>
     */
    private function warnings(Employee $employee): array
    {
        $warnings = [];

        if (! $employee->isVerified()) {
            $warnings[] = 'ID Card tersedia setelah data Anda diverifikasi HR.';
        }

        if (blank($employee->employee_number)) {
            $warnings[] = 'NUP / Nomor Pegawai belum tersedia.';
        } elseif (! $employee->hasValidEmployeeNumber()) {
            $warnings[] = 'NUP / Nomor Pegawai harus 10 digit angka.';
        }

        if (in_array($employee->employment_status, ['nonaktif', 'resign'], true)) {
            $warnings[] = 'ID Card tidak tersedia untuk status kepegawaian saat ini.';
        }

        return $warnings;
    }

    private function isValidForIdCard(Employee $employee): bool
    {
        return $employee->isEligibleForIdCard();
    }

    private function qrCodeSvg(?EmployeeQrToken $token): ?string
    {
        if (! $token || ! $token->isActive()) {
            return null;
        }

        try {
            return $this->qrCodeRenderer->render($this->tokenService->payloadFor($token));
        } catch (Throwable) {
            return null;
        }
    }
}
