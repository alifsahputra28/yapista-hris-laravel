<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeInvitationController extends Controller
{
    public function index(Request $request): View
    {
        $this->expireUnusedInvitations();

        $search = $request->string('search')->toString();

        $invitations = EmployeeInvitation::query()
            ->with(['employee.institution', 'employee.position', 'creator'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('invitation_code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($query) use ($search): void {
                            $query->where('full_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('institution_id'), function ($query) use ($request): void {
                $query->whereHas('employee', function ($query) use ($request): void {
                    $query->where('institution_id', $request->integer('institution_id'));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $institutions = Institution::query()
            ->orderBy('name')
            ->get();

        return view('invitations.index', compact('invitations', 'institutions', 'search'));
    }

    public function generate(Employee $employee): RedirectResponse
    {
        $this->expireUnusedInvitations();

        if ($employee->user_id !== null) {
            return redirect()
                ->route('employees.index')
                ->with('error', 'Pegawai ini sudah memiliki akun.');
        }

        $activeInvitation = $employee->invitations()
            ->where('status', 'unused')
            ->latest()
            ->first();

        if ($activeInvitation?->isValid()) {
            return redirect()
                ->route('invitations.index')
                ->with('error', 'Undangan aktif untuk pegawai ini sudah tersedia.');
        }

        $invitation = EmployeeInvitation::create([
            'employee_id' => $employee->id,
            'invitation_code' => $this->generateInvitationCode(),
            'email' => $employee->email,
            'phone' => $employee->phone,
            'status' => 'unused',
            'expired_at' => now()->addDays(14),
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('invitations.index')
            ->with('success', 'Undangan registrasi berhasil dibuat.')
            ->with('invitation_code', $invitation->invitation_code)
            ->with('invitation_link', route('invitation.register.show', $invitation->invitation_code));
    }

    public function revoke(EmployeeInvitation $invitation): RedirectResponse
    {
        if (! $invitation->isUnused()) {
            return redirect()
                ->route('invitations.index')
                ->with('error', 'Undangan hanya bisa dibatalkan jika status masih unused.');
        }

        $invitation->update([
            'status' => 'revoked',
        ]);

        return redirect()
            ->route('invitations.index')
            ->with('success', 'Undangan registrasi berhasil dibatalkan.');
    }

    private function expireUnusedInvitations(): void
    {
        EmployeeInvitation::query()
            ->where('status', 'unused')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update(['status' => 'expired']);
    }

    private function generateInvitationCode(): string
    {
        do {
            $code = 'YAPISTA-REG-'.$this->randomSuffix();
        } while (EmployeeInvitation::where('invitation_code', $code)->exists());

        return $code;
    }

    private function randomSuffix(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $suffix = '';

        for ($i = 0; $i < 6; $i++) {
            $suffix .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $suffix;
    }
}
