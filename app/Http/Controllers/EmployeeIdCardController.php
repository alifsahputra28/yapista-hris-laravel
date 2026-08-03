<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Support\IdCards\BarcodeRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeIdCardController extends Controller
{
    public function show(Employee $employee): View
    {
        $employee->load(['institution', 'position', 'user']);

        return view('id-cards.show', [
            'employee' => $employee,
            'barcodeBase64' => $this->barcodeBase64($employee),
            'barcodeSvg' => $this->barcodeSvg($employee),
            'warnings' => $this->warnings($employee, admin: true),
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
