<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeCertification;
use App\Models\EmployeeDocument;
use App\Models\EmployeeEducation;
use App\Services\EmployeeDocumentStorageService;
use App\Support\Documents\EmployeeDocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
        $documentTypes = EmployeeDocumentType::generalEmployeeTypes();

        return view('pegawai.documents.index', compact('employee', 'documents', 'documentTypes'));
    }

    public function store(StoreEmployeeDocumentRequest $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        if (! $employee->canEditProfileCompletion()) {
            return $this->redirectAfterAction($request)
                ->with('error', 'Dokumen tidak bisa diubah saat data sudah diajukan/diverifikasi.');
        }

        $validated = $request->validated();
        $target = $this->resolveTarget($employee, $validated);

        $file = $request->file('file');
        $document = $employee->documents()
            ->where('document_type', $validated['document_type'])
            ->where('document_slot', $target['document_slot'])
            ->first();
        $oldPath = $document?->file_path;

        try {
            $path = $this->storage->store($file, $employee->id);

            try {
                DB::transaction(function () use ($employee, $file, $path, $validated, $target): void {
                    $employee->documents()->updateOrCreate(
                        [
                            'document_type' => $validated['document_type'],
                            'document_slot' => $target['document_slot'],
                        ],
                        [
                            'employee_education_id' => $target['employee_education_id'],
                            'employee_certification_id' => $target['employee_certification_id'],
                            'file_path' => $path,
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType() ?: null,
                            'file_size' => $file->getSize() ?: null,
                            'status' => 'pending',
                            'note' => null,
                            'uploaded_at' => now(),
                        ],
                    );
                });
            } catch (Throwable $exception) {
                $this->storage->deletePrivatePath($path);

                throw $exception;
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectAfterAction($request)
                ->with('error', 'Dokumen gagal disimpan. Silakan coba kembali.');
        }

        if ($oldPath && $oldPath !== $path) {
            $this->storage->deletePath($oldPath);
        }

        return $this->redirectAfterAction($request)
            ->with('success', 'Dokumen berhasil diupload.');
    }

    public function destroy(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $employee = $this->currentEmployee();

        if (! $employee) {
            return $this->missingEmployeeRedirect();
        }

        Gate::authorize('delete', $document);

        abort_unless(EmployeeDocumentType::employeeMayUpload($document->document_type), 403);

        if (! $employee->canEditProfileCompletion()) {
            return $this->redirectAfterAction($request)
                ->with('error', 'Dokumen tidak bisa dihapus saat data sudah diajukan/diverifikasi.');
        }

        if ($document->isValid()) {
            return $this->redirectAfterAction($request)
                ->with('error', 'Dokumen valid tidak bisa dihapus.');
        }

        $path = $document->file_path;

        DB::transaction(function () use ($document): void {
            $document->delete();
        });

        $this->storage->deletePath($path);

        return $this->redirectAfterAction($request)
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{document_slot: string, employee_education_id: int|null, employee_certification_id: int|null}
     */
    private function resolveTarget(Employee $employee, array $validated): array
    {
        $type = $validated['document_type'];

        if (EmployeeDocumentType::isEducation($type)) {
            if (blank($validated['employee_education_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'employee_education_id' => 'Pilih riwayat pendidikan untuk dokumen ini.',
                ]);
            }

            /** @var EmployeeEducation $education */
            $education = $employee->educations()->findOrFail($validated['employee_education_id']);

            return [
                'document_slot' => "education:{$education->id}",
                'employee_education_id' => $education->id,
                'employee_certification_id' => null,
            ];
        }

        if (EmployeeDocumentType::isCertification($type)) {
            if (blank($validated['employee_certification_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'employee_certification_id' => 'Pilih sertifikasi untuk dokumen ini.',
                ]);
            }

            /** @var EmployeeCertification $certification */
            $certification = $employee->certifications()->findOrFail($validated['employee_certification_id']);

            return [
                'document_slot' => "certification:{$certification->id}",
                'employee_education_id' => null,
                'employee_certification_id' => $certification->id,
            ];
        }

        return [
            'document_slot' => 'primary',
            'employee_education_id' => null,
            'employee_certification_id' => null,
        ];
    }

    private function redirectAfterAction(Request $request): RedirectResponse
    {
        return $request->input('document_context') === 'wizard'
            ? redirect()->route('pegawai.profile.wizard.show', 'review')
            : redirect()->route('pegawai.documents.index');
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
