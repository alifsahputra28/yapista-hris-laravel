<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Support\Documents\EmployeeDocumentType;
use Illuminate\Support\Facades\DB;

class EmployeeProfileSubmissionService
{
    public function __construct(
        private readonly EmployeeProfileProgressService $progressService,
        private readonly EmployeeDocumentStorageService $documentStorage,
    ) {}

    /** @return array<string, mixed> */
    public function inspect(Employee $employee): array
    {
        $employee->loadMissing([
            'user',
            'familyMembers',
            'educations.documents',
            'certifications.documents',
            'administrativeDetail',
            'documents',
        ]);

        $progress = $this->progressService->calculate($employee);
        $detail = $employee->administrativeDetail;
        $mainRequirements = [
            'ktp' => ['required' => true, 'requirement' => 'Wajib'],
            'kk' => ['required' => true, 'requirement' => 'Wajib'],
            'buku_rekening' => ['required' => true, 'requirement' => 'Wajib'],
            'dokumen_pajak' => [
                'required' => $detail?->tax_status === 'registered' && $detail->nik_used_as_tax_id !== true,
                'requirement' => 'Kondisional',
            ],
            'bpjs_kesehatan' => [
                'required' => $detail?->bpjs_health_status === 'active',
                'requirement' => 'Kondisional',
            ],
            'bpjs_ketenagakerjaan' => [
                'required' => $detail?->bpjs_employment_status === 'active',
                'requirement' => 'Kondisional',
            ],
        ];

        $mainDocuments = [];
        foreach ($mainRequirements as $type => $requirement) {
            $mainDocuments[$type] = array_merge(
                $this->documentState($employee, $type, 'primary'),
                $requirement,
                ['label' => EmployeeDocumentType::label($type)]
            );
        }

        $warnings = [];
        $educationDocuments = [];
        $educationCount = $employee->educations->count();
        foreach ($employee->educations as $education) {
            $ijazah = $this->documentState($employee, 'ijazah', "education:{$education->id}");
            if (! $ijazah['completed'] && $education->is_highest && $educationCount === 1) {
                $legacy = $this->documentState($employee, 'ijazah', 'primary');
                if ($legacy['completed']) {
                    $ijazah = array_merge($legacy, ['legacy_fallback' => true]);
                    $warnings[] = 'Ijazah pendidikan tertinggi masih menggunakan dokumen legacy umum.';
                }
            }

            $educationDocuments[] = [
                'education_id' => $education->id,
                'education_label' => $education->education_level_label.' - '.$education->institution_name,
                'is_highest' => (bool) $education->is_highest,
                'ijazah' => array_merge($ijazah, [
                    'label' => EmployeeDocumentType::label('ijazah'),
                    'required' => (bool) $education->is_highest,
                    'requirement' => $education->is_highest ? 'Wajib' : 'Opsional',
                ]),
                'transkrip' => array_merge(
                    $this->documentState($employee, 'transkrip', "education:{$education->id}"),
                    ['label' => EmployeeDocumentType::label('transkrip'), 'required' => false, 'requirement' => 'Opsional']
                ),
            ];
        }

        $certificationDocuments = $employee->certifications->map(fn ($certification): array => [
            'certification_id' => $certification->id,
            'certification_label' => $certification->name,
            'issuer' => $certification->issuer,
            'document' => array_merge(
                $this->documentState($employee, 'sertifikat', "certification:{$certification->id}"),
                ['label' => EmployeeDocumentType::label('sertifikat'), 'required' => false, 'requirement' => 'Opsional']
            ),
        ])->values()->all();

        $requiredDocuments = collect($mainDocuments)
            ->filter(fn (array $item): bool => $item['required'])
            ->merge(collect($educationDocuments)->pluck('ijazah')->filter(fn (array $item): bool => $item['required']));
        $missingDocuments = $requiredDocuments
            ->reject(fn (array $item): bool => $item['completed'])
            ->pluck('label')
            ->values()
            ->all();
        $missingData = collect($progress['sections'])
            ->reject(fn (array $section): bool => $section['completed'])
            ->pluck('label')
            ->values()
            ->all();
        $activeAccount = $employee->user?->status === 'active';
        if (! $activeAccount) {
            $missingData[] = 'Akun pegawai aktif';
        }

        return [
            'can_submit' => $missingData === []
                && $missingDocuments === []
                && $employee->canEditProfileCompletion(),
            'data_progress' => $progress,
            'main_documents' => $mainDocuments,
            'education_documents' => $educationDocuments,
            'certification_documents' => $certificationDocuments,
            'required_documents' => $requiredDocuments->count(),
            'completed_required_documents' => $requiredDocuments->where('completed', true)->count(),
            'missing_data' => $missingData,
            'missing_documents' => $missingDocuments,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /** @return array{submitted: bool, already_submitted: bool, checklist: array<string, mixed>, employee: Employee} */
    public function submit(Employee $employee, User $submitter): array
    {
        return DB::transaction(function () use ($employee, $submitter): array {
            $locked = Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            abort_unless((int) $locked->user_id === (int) $submitter->id, 403);

            if ($locked->isProfileSubmitted()) {
                return [
                    'submitted' => false,
                    'already_submitted' => true,
                    'checklist' => $this->inspect($locked),
                    'employee' => $locked,
                ];
            }

            $checklist = $this->inspect($locked);
            if (! $checklist['can_submit']) {
                return [
                    'submitted' => false,
                    'already_submitted' => false,
                    'checklist' => $checklist,
                    'employee' => $locked,
                ];
            }

            $locked->forceFill([
                'profile_review_status' => Employee::PROFILE_REVIEW_SUBMITTED,
                'profile_submitted_at' => now(),
                'profile_reviewed_at' => null,
                'profile_reviewed_by' => null,
                'profile_review_note' => null,
                'profile_rejected_sections' => null,
            ])->save();

            return [
                'submitted' => true,
                'already_submitted' => false,
                'checklist' => $checklist,
                'employee' => $locked,
            ];
        }, 3);
    }

    /** @return array{document_id: int|null, original_name: string|null, document_status: string|null, uploaded: bool, file_available: bool, completed: bool, status: string, legacy_fallback: bool} */
    private function documentState(Employee $employee, string $type, string $slot): array
    {
        /** @var EmployeeDocument|null $document */
        $document = $employee->documents->first(
            fn (EmployeeDocument $item): bool => $item->document_type === $type
                && ($item->document_slot ?? 'primary') === $slot
        );
        $fileAvailable = $document !== null && $this->documentStorage->locate($document) !== null;

        return [
            'document_id' => $document?->id,
            'original_name' => $document?->original_name,
            'document_status' => $document?->status,
            'uploaded' => $document !== null,
            'file_available' => $fileAvailable,
            'completed' => $fileAvailable,
            'status' => $document === null ? 'Belum diunggah' : ($fileAvailable ? 'Sudah diunggah' : 'File tidak tersedia'),
            'legacy_fallback' => false,
        ];
    }
}
