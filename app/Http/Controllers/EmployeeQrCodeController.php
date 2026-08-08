<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeQrTokenService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeQrCodeController extends Controller
{
    public function __construct(private readonly EmployeeQrTokenService $tokenService) {}

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        try {
            $this->tokenService->generate($employee, $request->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'QR Code berhasil dibuat.');
    }

    public function regenerate(Request $request, Employee $employee): RedirectResponse
    {
        try {
            $this->tokenService->regenerate($employee, $request->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'QR Code baru berhasil dibuat. QR Code lama sudah dinonaktifkan.');
    }
}
