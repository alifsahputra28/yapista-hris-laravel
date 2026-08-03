<?php

namespace App\Policies;

use App\Models\EmployeeDocument;
use App\Models\User;

class EmployeeDocumentPolicy
{
    public function view(User $user, EmployeeDocument $employeeDocument): bool
    {
        if ($user->isSuperAdmin() || $user->isHrAdmin()) {
            return true;
        }

        return $user->isPegawai()
            && (int) $user->employee?->id === (int) $employeeDocument->employee_id;
    }

    public function download(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $this->view($user, $employeeDocument);
    }

    public function delete(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $this->view($user, $employeeDocument);
    }

    public function update(User $user, EmployeeDocument $employeeDocument): bool
    {
        return $this->view($user, $employeeDocument);
    }
}
