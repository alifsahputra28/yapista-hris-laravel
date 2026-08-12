<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEmployeesRequest;
use App\Services\EmployeeImportService;
use App\Services\EmployeeImportTemplateService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportController extends Controller
{
    public function template(EmployeeImportTemplateService $templateService): StreamedResponse
    {
        return $templateService->download();
    }

    public function store(ImportEmployeesRequest $request, EmployeeImportService $importService): RedirectResponse
    {
        $summary = $importService->import($request->file('file'), $request->user());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Import data pegawai selesai diproses.')
            ->with('import_summary', $summary);
    }
}
