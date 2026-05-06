<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeDocumentController extends Controller
{
    public function index(): RedirectResponse|View
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        $employee->load('documents');
        $documents = $employee->documents()->latest('uploaded_at')->get();
        $documentTypes = EmployeeDocument::DOCUMENT_TYPES;

        return view('pegawai.documents.index', compact('employee', 'documents', 'documentTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        if (! $employee->canEditProfile()) {
            return redirect()
                ->route('pegawai.documents.index')
                ->with('error', 'Dokumen tidak bisa diubah saat data sudah diajukan/diverifikasi.');
        }

        $validated = $request->validate([
            'document_type' => ['required', Rule::in(array_keys(EmployeeDocument::DOCUMENT_TYPES))],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('employees/documents', 'public');

        $document = $employee->documents()
            ->where('document_type', $validated['document_type'])
            ->first();

        if ($document?->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $employee->documents()->updateOrCreate(
            ['document_type' => $validated['document_type']],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'pending',
                'note' => null,
                'uploaded_at' => now(),
            ],
        );

        if ($employee->isRejected()) {
            $employee->update([
                'verification_status' => 'draft',
                'verification_note' => null,
            ]);
        }

        return redirect()
            ->route('pegawai.documents.index')
            ->with('success', 'Dokumen berhasil diupload.');
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        if ((int) $document->employee_id !== (int) $employee->id) {
            abort(403);
        }

        if (! $employee->canEditProfile()) {
            return redirect()
                ->route('pegawai.documents.index')
                ->with('error', 'Dokumen tidak bisa dihapus saat data sudah diajukan/diverifikasi.');
        }

        if ($document->isValid()) {
            return redirect()
                ->route('pegawai.documents.index')
                ->with('error', 'Dokumen valid tidak bisa dihapus.');
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()
            ->route('pegawai.documents.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    private function currentEmployee(): ?Employee
    {
        return Auth::user()?->employee;
    }

    private function missingEmployeeRedirect(): RedirectResponse
    {
        return redirect()
            ->route('pegawai.dashboard')
            ->with('error', 'Data pegawai Anda belum terhubung. Silakan hubungi HR/Admin.');
    }
}
