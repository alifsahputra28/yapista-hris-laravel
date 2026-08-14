<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeePhotoStorageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePhotoController extends Controller
{
    public function __invoke(
        Request $request,
        Employee $employee,
        EmployeePhotoStorageService $storage
    ): StreamedResponse {
        $user = $request->user();
        $isHr = $user?->isSuperAdmin() === true || $user?->isHrAdmin() === true;
        $isOwner = $user?->isPegawai() === true && $user->employee?->is($employee);

        abort_unless($isHr || $isOwner, 404);

        $location = $storage->locate($employee->photo);
        abort_if($location === null, 404);

        return $storage->inlineResponse($location);
    }
}
