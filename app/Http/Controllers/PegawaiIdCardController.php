<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\IdCards\BarcodeRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PegawaiIdCardController extends Controller
{
    public function show(): View
    {
        $employee = auth()->user()?->employee?->load(['institution', 'position']);

        return view('pegawai.id-card.show', [
            'employee' => $employee,
            'barcodeBase64' => $employee ? $this->barcodeBase64($employee) : null,
            'barcodeSvg' => $employee ? $this->barcodeSvg($employee) : null,
            'warnings' => $employee
                ? $this->warnings($employee)
                : ['Data pegawai belum terhubung dengan akun Anda.'],
            'isValidForIdCard' => $employee ? $this->isValidForIdCard($employee) : false,
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

        return $warnings;
    }

    private function isValidForIdCard(Employee $employee): bool
    {
        return $employee->isVerified()
            && $employee->hasValidEmployeeNumber();
    }

    private function barcodeBase64(Employee $employee): ?string
    {
        if (! $this->isValidForIdCard($employee)) {
            return null;
        }

        return app(BarcodeRenderer::class)->base64Png($employee->employee_number);
    }

    private function barcodeSvg(Employee $employee): ?string
    {
        if (! $this->isValidForIdCard($employee) || $this->barcodeBase64($employee)) {
            return null;
        }

        return app(BarcodeRenderer::class)->code128Svg($employee->employee_number);
    }
}
