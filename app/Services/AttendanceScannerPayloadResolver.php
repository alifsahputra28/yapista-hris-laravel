<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\Attendances\ScannerPayloadResolution;

class AttendanceScannerPayloadResolver
{
    public function __construct(
        private readonly EmployeeQrTokenService $qrTokenService,
    ) {}

    public function resolve(string $payload): ScannerPayloadResolution
    {
        $payload = trim($payload);

        if (str_starts_with($payload, EmployeeQrTokenService::PAYLOAD_PREFIX)) {
            $qrToken = $this->qrTokenService->resolvePayload($payload);

            return $qrToken?->employee
                ? ScannerPayloadResolution::secure($qrToken)
                : ScannerPayloadResolution::rejected('QR Code tidak valid atau sudah tidak aktif.');
        }

        $legacyPayload = preg_replace('/\s+/u', '', $payload);

        if (is_string($legacyPayload) && preg_match('/\A\d{10}\z/D', $legacyPayload) === 1) {
            if (! config('attendance.allow_legacy_nup_qr', true)) {
                return ScannerPayloadResolution::rejected('QR Code tidak dikenali.');
            }

            $employee = Employee::query()
                ->where('employee_number', $legacyPayload)
                ->first();

            return $employee
                ? ScannerPayloadResolution::legacy($employee)
                : ScannerPayloadResolution::rejected('Pegawai tidak ditemukan.');
        }

        return ScannerPayloadResolution::rejected('QR Code tidak dikenali.');
    }
}
