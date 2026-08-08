<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Services\EmployeeQrTokenService;
use App\Support\IdCards\QrCodeRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class EmployeeIdCardController extends Controller
{
    public function __construct(
        private readonly EmployeeQrTokenService $tokenService,
        private readonly QrCodeRenderer $qrCodeRenderer,
    ) {}

    public function show(Employee $employee): View
    {
        $employee->load(['institution', 'position', 'user', 'activeQrToken']);
        $qrCodeSvg = $this->qrCodeSvg($employee->activeQrToken);
        $warnings = $this->warnings($employee, admin: true);

        if ($this->isValidForIdCard($employee) && ! $employee->activeQrToken) {
            $warnings[] = 'QR Code belum tersedia.';
        } elseif ($employee->activeQrToken && $qrCodeSvg === null) {
            $warnings[] = 'QR Code tidak dapat ditampilkan. Silakan buat ulang QR Code.';
        }

        return view('id-cards.show', [
            'employee' => $employee,
            'qrCodeSvg' => $qrCodeSvg,
            'hasActiveQrToken' => $employee->activeQrToken !== null,
            'warnings' => $warnings,
            'isValidForIdCard' => $this->isValidForIdCard($employee),
        ]);
    }

    public function download(Employee $employee): RedirectResponse
    {
        return redirect()
            ->route('employees.id-card.show', $employee)
            ->with('error', 'Download PDF ID Card belum tersedia. Silakan gunakan print dari browser.');
    }

    /**
     * @return array<int, string>
     */
    private function warnings(Employee $employee, bool $admin = false): array
    {
        $warnings = [];

        if (! $employee->isVerified()) {
            $warnings[] = $admin
                ? 'ID Card hanya dapat digunakan untuk pegawai yang sudah terverifikasi.'
                : 'ID Card tersedia setelah data Anda diverifikasi HR.';
        }

        if (blank($employee->employee_number)) {
            $warnings[] = $admin
                ? 'NUP / Nomor Pegawai belum diisi.'
                : 'NUP / Nomor Pegawai belum tersedia.';
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
