<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\CreateEmployeeAccountRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $role = in_array($request->string('role')->toString(), User::ROLES, true)
            ? $request->string('role')->toString()
            : '';
        $status = in_array($request->string('status')->toString(), User::STATUSES, true)
            ? $request->string('status')->toString()
            : '';
        $perPage = in_array($request->integer('per_page'), [15, 25, 50], true)
            ? $request->integer('per_page')
            : 15;

        $users = User::query()
            ->with([
                'employee:id,user_id,institution_id,full_name,employee_number',
                'employee.institution:id,name',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($query) use ($search): void {
                            $query->where('full_name', 'like', "%{$search}%")
                                ->orWhere('employee_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'perPage' => $perPage,
            'roleLabels' => User::ROLE_LABELS,
            'statusLabels' => User::STATUS_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'managedUser' => new User(['role' => 'pegawai', 'status' => 'active']),
            'employees' => $this->availableEmployees(),
            'roleLabels' => User::ROLE_LABELS,
            'statusLabels' => User::STATUS_LABELS,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'status' => $validated['status'],
                'password' => Hash::make($validated['password']),
            ]);

            $this->syncEmployee($user, $validated['employee_id'] ?? null);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function createEmployeeAccount(Employee $employee): View
    {
        $employee->load('institution');

        return view('users.employee-account-create', [
            'employee' => $employee,
            'roleLabels' => User::ROLE_LABELS,
        ]);
    }

    public function storeEmployeeAccount(CreateEmployeeAccountRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $employee): void {
            $lockedEmployee = Employee::query()->lockForUpdate()->findOrFail($employee->id);

            if ($lockedEmployee->user_id !== null) {
                throw ValidationException::withMessages([
                    'employee' => 'Pegawai sudah terhubung ke akun lain.',
                ]);
            }

            $user = User::create([
                'name' => $lockedEmployee->full_name,
                'email' => $validated['email'] ?? null,
                'role' => $validated['role'],
                'status' => 'active',
                'password' => Hash::make($validated['password']),
            ]);

            $lockedEmployee->update(['user_id' => $user->id]);
            $lockedEmployee->invitations()->where('status', 'unused')->update(['status' => 'revoked']);
        });

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Akun login pegawai berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        $user->load('employee');

        return view('users.edit', [
            'managedUser' => $user,
            'employees' => $this->availableEmployees($user),
            'roleLabels' => User::ROLE_LABELS,
            'statusLabels' => User::STATUS_LABELS,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $user, $validated): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $this->ensureSafeAdministrativeChange(
                $request->user(),
                $lockedUser,
                $validated['role'],
                $validated['status']
            );

            $lockedUser->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'status' => $validated['status'],
            ]);

            $this->syncEmployee($lockedUser, $validated['employee_id'] ?? null);
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $newStatus = $lockedUser->isActive() ? 'inactive' : 'active';

            $this->ensureSafeAdministrativeChange(
                $request->user(),
                $lockedUser,
                $lockedUser->role,
                $newStatus
            );

            $lockedUser->update(['status' => $newStatus]);
        });

        $message = $user->fresh()->isActive()
            ? 'User berhasil diaktifkan kembali.'
            : 'User berhasil dinonaktifkan. Histori data tetap tersimpan.';

        return redirect()->back()->with('success', $message);
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'Password berhasil diperbarui.');
    }

    /** @return Collection<int, Employee> */
    private function availableEmployees(?User $user = null)
    {
        return Employee::query()
            ->select(['id', 'user_id', 'institution_id', 'full_name', 'employee_number'])
            ->with('institution:id,name')
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id');

                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('full_name')
            ->get();
    }

    private function syncEmployee(User $user, mixed $employeeId): void
    {
        $selectedEmployee = null;

        if ($employeeId !== null) {
            $selectedEmployee = Employee::query()->lockForUpdate()->findOrFail($employeeId);

            if ($selectedEmployee->user_id !== null && $selectedEmployee->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'employee_id' => 'Pegawai tidak tersedia atau sudah terhubung ke akun lain.',
                ]);
            }
        }

        Employee::query()
            ->where('user_id', $user->id)
            ->when($selectedEmployee, fn ($query) => $query->whereKeyNot($selectedEmployee->id))
            ->update(['user_id' => null]);

        if (! $selectedEmployee) {
            return;
        }

        $selectedEmployee->update(['user_id' => $user->id]);
        $selectedEmployee->invitations()
            ->where('status', 'unused')
            ->update(['status' => 'revoked']);
    }

    private function ensureSafeAdministrativeChange(
        ?User $actor,
        User $target,
        string $newRole,
        string $newStatus
    ): void {
        $removesActiveSuperAdmin = $target->role === 'super_admin'
            && $target->status === 'active'
            && ($newRole !== 'super_admin' || $newStatus !== 'active');

        if ($removesActiveSuperAdmin) {
            $activeSuperAdmins = User::query()
                ->where('role', 'super_admin')
                ->where('status', 'active')
                ->lockForUpdate()
                ->get(['id']);

            if ($activeSuperAdmins->count() <= 1) {
                throw ValidationException::withMessages([
                    'status' => 'Minimal satu Super Admin aktif harus tersedia.',
                ]);
            }
        }

        if ($actor?->is($target) && $newStatus !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        if ($actor?->is($target) && $newRole !== 'super_admin') {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat menurunkan role akun sendiri.',
            ]);
        }
    }
}
