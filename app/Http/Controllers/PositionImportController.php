<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPositionsRequest;
use App\Services\PositionImportService;
use App\Services\PositionImportTemplateService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionImportController extends Controller
{
    public function template(PositionImportTemplateService $templateService): StreamedResponse
    {
        return $templateService->download();
    }

    public function store(ImportPositionsRequest $request, PositionImportService $importService): RedirectResponse
    {
        $summary = $importService->import($request->file('file'));

        return redirect()->route('positions.index')
            ->with('success', 'Import Jabatan selesai.')
            ->with('position_import_summary', $summary);
    }
}
