<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Institution;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $verificationStatus = $request->input('verification_status', 'submitted');

        if (! in_array($verificationStatus, ['submitted', 'verified', 'rejected'], true)) {
            $verificationStatus = 'submitted';
        }

        $employees = Employee::query()
            ->with(['user', 'institution', 'position', 'documents'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('employee_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('institution_id'), function ($query) use ($request): void {
                $query->where('institution_id', $request->integer('institution_id'));
            })
            ->when($request->filled('position_id'), function ($query) use ($request): void {
                $query->where('position_id', $request->integer('position_id'));
            })
            ->when($request->filled('employee_type'), function ($query) use ($request): void {
                $query->where('employee_type', $request->string('employee_type')->toString());
            })
            ->where('verification_status', $verificationStatus)
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $institutions = Institution::query()->orderBy('name')->get();
        $positions = Position::query()->with('institution')->orderBy('name')->get();

        return view('verifications.index', compact('employees', 'institutions', 'positions', 'search', 'verificationStatus'));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['user', 'institution', 'position', 'documents', 'verifier']);

        return view('verifications.show', compact('employee'));
    }

    public function approve(Employee $employee): RedirectResponse
    {
        if (! $employee->isSubmitted()) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', 'Hanya data dengan status submitted yang bisa diverifikasi.');
        }

        $employee->load('documents');

        if (! $employee->hasRequiredProfileData()) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', 'Data wajib belum lengkap. Pastikan nama, NIK, nomor HP, alamat, dan foto sudah terisi.');
        }

        $ktpDocument = $employee->documents->firstWhere('document_type', 'ktp');

        if (! $ktpDocument) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', 'Dokumen KTP belum diupload.');
        }

        if (! $ktpDocument->isValid()) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', 'Dokumen KTP harus berstatus valid sebelum pegawai diverifikasi.');
        }

        if (empty($employee->join_date)) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', 'Tanggal masuk pegawai belum diisi.');
        }

        if (empty($employee->foundation_registry_number)) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', 'Nomor urut buku yayasan belum diisi.');
        }

        $errorMessage = DB::transaction(function () use ($employee): ?string {
            $employee = Employee::query()
                ->whereKey($employee->id)
                ->lockForUpdate()
                ->firstOrFail();

            $employeeNumber = $this->generateEmployeeNumber($employee);

            if (Employee::where('employee_number', $employeeNumber)
                ->whereKeyNot($employee->id)
                ->exists()) {
                return 'Nomor pegawai sudah digunakan. Periksa kembali nomor urut buku yayasan.';
            }

            $employee->employee_number = $employeeNumber;
            $employee->verification_status = 'verified';
            $employee->verification_note = null;
            $employee->verified_by = Auth::id();
            $employee->verified_at = now();
            $employee->save();

            return null;
        });

        if ($errorMessage !== null) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', $errorMessage);
        }

        return redirect()
            ->route('verifications.show', $employee)
            ->with('success', 'Data pegawai berhasil diverifikasi.');
    }

    public function reject(Request $request, Employee $employee): RedirectResponse
    {
        if (! $employee->isSubmitted()) {
            return redirect()
                ->route('verifications.show', $employee)
                ->with('error', 'Hanya data dengan status submitted yang bisa ditolak.');
        }

        $validated = $request->validate([
            'verification_note' => ['required', 'string', 'max:1000'],
        ]);

        $employee->update([
            'verification_status' => 'rejected',
            'verification_note' => $validated['verification_note'],
            'verified_by' => Auth::id(),
            'verified_at' => null,
        ]);

        return redirect()
            ->route('verifications.index')
            ->with('success', 'Data pegawai ditolak dan dikembalikan untuk perbaikan.');
    }

    public function updateDocumentStatus(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['valid', 'rejected'])],
            'note' => [$request->input('status') === 'rejected' ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);

        $document->load('employee');
        $note = $validated['note'] ?? null;

        $document->update([
            'status' => $validated['status'],
            'note' => $note,
        ]);

        if ($validated['status'] === 'rejected' && $document->employee?->isSubmitted()) {
            $document->employee->update([
                'verification_status' => 'rejected',
                'verification_note' => 'Dokumen '.$document->document_type_label.' ditolak: '.$note,
                'verified_by' => Auth::id(),
                'verified_at' => null,
            ]);
        }

        return redirect()
            ->route('verifications.show', $document->employee)
            ->with('success', 'Status dokumen berhasil diperbarui.');
    }

    private function generateEmployeeNumber(Employee $employee): string
    {
        return '777.'
            .$employee->join_date->format('my')
            .'.'
            .str_pad((string) $employee->foundation_registry_number, 4, '0', STR_PAD_LEFT);
    }
}
