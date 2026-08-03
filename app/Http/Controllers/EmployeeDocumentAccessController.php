<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentStorageService;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentAccessController extends Controller
{
    public function view(
        EmployeeDocument $employeeDocument,
        EmployeeDocumentStorageService $storage
    ): StreamedResponse {
        Gate::authorize('view', $employeeDocument);

        $location = $storage->locate($employeeDocument);

        abort_if($location === null, 404, 'File dokumen tidak ditemukan.');

        return $storage->inlineResponse($employeeDocument, $location);
    }

    public function download(
        EmployeeDocument $employeeDocument,
        EmployeeDocumentStorageService $storage
    ): StreamedResponse {
        Gate::authorize('download', $employeeDocument);

        $location = $storage->locate($employeeDocument);

        abort_if($location === null, 404, 'File dokumen tidak ditemukan.');

        return $storage->downloadResponse($employeeDocument, $location);
    }
}
