<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Services\EmployeeDocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmployeeDocumentController extends Controller
{
    public function __construct(private readonly EmployeeDocumentStorageService $storage) {}

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
        $document = $employee->documents()
            ->where('document_type', $validated['document_type'])
            ->first();
        $oldPath = $document?->file_path;

        try {
            $path = $this->storage->store($file, $employee->id);

            try {
                DB::transaction(function () use ($employee, $file, $path, $validated): void {
                    $employee->documents()->updateOrCreate(
                        ['document_type' => $validated['document_type']],
                        [
                            'file_path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType() ?: null,
                            'file_size' => $file->getSize() ?: null,
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
                });
            } catch (Throwable $exception) {
                $this->storage->deletePrivatePath($path);

                throw $exception;
            }
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('pegawai.documents.index')
                ->with('error', 'Dokumen gagal disimpan. Silakan coba kembali.');
        }

        if ($oldPath && $oldPath !== $path) {
            $this->storage->deletePath($oldPath);
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

        Gate::authorize('delete', $document);

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

        $path = $document->file_path;

        DB::transaction(function () use ($document): void {
            $document->delete();
        });

        $this->storage->deletePath($path);

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
