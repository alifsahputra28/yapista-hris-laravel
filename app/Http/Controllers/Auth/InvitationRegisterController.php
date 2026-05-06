<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeeInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InvitationRegisterController extends Controller
{
    public function show(string $code): RedirectResponse|View
    {
        $invitation = $this->findInvitation($code);

        if (! $invitation) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Kode undangan tidak ditemukan.']);
        }

        $this->expireIfNeeded($invitation);

        $employee = $invitation->employee;
        $error = $this->validationMessage($invitation);

        return view('auth.invitation-register', compact('invitation', 'employee', 'error'));
    }

    public function register(Request $request, string $code): RedirectResponse
    {
        $invitation = $this->findInvitation($code);

        if (! $invitation) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Kode undangan tidak ditemukan.']);
        }

        $this->expireIfNeeded($invitation);

        $message = $this->validationMessage($invitation);

        if ($message !== null) {
            return back()->withErrors(['invitation' => $message])->withInput();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($validated, $invitation): User {
            $employee = $invitation->employee()->lockForUpdate()->firstOrFail();

            if ($employee->user_id !== null || ! $invitation->fresh()->isValid()) {
                abort(403, 'Kode undangan tidak valid.');
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'pegawai',
                'status' => 'active',
            ]);

            $employee->update([
                'user_id' => $user->id,
                'email' => $employee->email ?: $user->email,
            ]);

            $invitation->update([
                'status' => 'used',
                'used_at' => now(),
            ]);

            return $user;
        });

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('pegawai.dashboard');
    }

    private function findInvitation(string $code): ?EmployeeInvitation
    {
        return EmployeeInvitation::with(['employee.institution', 'employee.position'])
            ->where('invitation_code', $code)
            ->first();
    }

    private function expireIfNeeded(EmployeeInvitation $invitation): void
    {
        if ($invitation->isUnused() && $invitation->expired_at !== null && $invitation->expired_at->isPast()) {
            $invitation->update(['status' => 'expired']);
            $invitation->refresh();
            $invitation->load(['employee.institution', 'employee.position']);
        }
    }

    private function validationMessage(EmployeeInvitation $invitation): ?string
    {
        if (! $invitation->employee) {
            return 'Data pegawai untuk undangan ini tidak ditemukan.';
        }

        if ($invitation->employee->user_id !== null) {
            return 'Pegawai ini sudah memiliki akun.';
        }

        if ($invitation->isUsed()) {
            return 'Kode undangan sudah digunakan.';
        }

        if ($invitation->isRevoked()) {
            return 'Kode undangan sudah dibatalkan.';
        }

        if ($invitation->isExpired()) {
            return 'Kode undangan sudah kedaluwarsa.';
        }

        if (! $invitation->isValid()) {
            return 'Kode undangan tidak valid.';
        }

        return null;
    }
}
