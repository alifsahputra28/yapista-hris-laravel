<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Support\Imports\EmployeeImportColumns;
use App\Support\Imports\EmployeeImportRowException;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use Throwable;

class EmployeeImportService
{
    private const MAX_DATA_ROWS = 1000;

    public function __construct(private readonly EmployeeQrTokenService $qrTokenService) {}

    /**
     * @return array{processed: int, created: int, skipped: int, failed: int, verified: int, draft: int, qr_tokens_created: int, invitations_created: int, errors: list<string>}
     */
    public function import(UploadedFile $file, User $creator): array
    {
        [$rows, $columnMap] = $this->readRows($file);
        $dataRows = array_slice($rows, 1);

        if (count($dataRows) > self::MAX_DATA_ROWS) {
            throw ValidationException::withMessages([
                'file' => 'File import maksimal berisi '.self::MAX_DATA_ROWS.' baris data.',
            ]);
        }

        $institutions = Institution::query()
            ->where('status', 'active')
            ->get()
            ->keyBy(fn (Institution $institution): string => $this->lookupKey($institution->name));
        $positions = Position::query()
            ->where('status', 'active')
            ->get()
            ->keyBy(fn (Position $position): string => $position->institution_id.'|'.$this->lookupKey($position->name));

        $summary = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'verified' => 0,
            'draft' => 0,
            'qr_tokens_created' => 0,
            'invitations_created' => 0,
            'errors' => [],
        ];

        foreach ($dataRows as $index => $row) {
            $excelRow = $index + 2;

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $summary['processed']++;

            try {
                $prepared = $this->prepareRow($row, $columnMap, $institutions, $positions);

                $result = DB::transaction(function () use ($prepared, $creator): array {
                    $this->assertNoDatabaseConflict($prepared);

                    $verified = $prepared['employee_number'] !== null;
                    $employee = Employee::create([
                        'institution_id' => $prepared['institution_id'],
                        'position_id' => $prepared['position_id'],
                        'employee_number' => $prepared['employee_number'],
                        'full_name' => $prepared['full_name'],
                        'email' => $prepared['personal_email'],
                        'employee_type' => $prepared['employee_type'],
                        'employment_status' => $prepared['employment_status'],
                        'join_date' => $prepared['join_date'],
                        'verification_status' => $verified ? 'verified' : 'draft',
                        'verified_by' => $verified ? $creator->id : null,
                        'verified_at' => $verified ? now() : null,
                    ]);

                    EmployeeInvitation::create([
                        'employee_id' => $employee->id,
                        'invitation_code' => $this->generateInvitationCode(),
                        'email' => $prepared['login_email'],
                        'status' => 'unused',
                        'expired_at' => now()->addDays(14),
                        'created_by' => $creator->id,
                    ]);

                    if ($verified) {
                        $this->qrTokenService->generate($employee, $creator);
                    }

                    return ['verified' => $verified];
                });

                $summary['created']++;
                $summary[$result['verified'] ? 'verified' : 'draft']++;
                $summary['invitations_created']++;
                $summary['qr_tokens_created'] += $result['verified'] ? 1 : 0;
            } catch (EmployeeImportRowException $exception) {
                $summary[$exception->skipped ? 'skipped' : 'failed']++;
                if (count($summary['errors']) < 25) {
                    $summary['errors'][] = "Baris {$excelRow}: {$exception->getMessage()}";
                }
            }
        }

        if ($summary['processed'] === 0) {
            throw ValidationException::withMessages([
                'file' => 'File tidak berisi data pegawai untuk diimport.',
            ]);
        }

        return $summary;
    }

    /** @return array{array<int, array<int, mixed>>, array<int, string>} */
    private function readRows(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'file' => 'File tidak dapat diproses. Pastikan menggunakan template Excel terbaru.',
            ]);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'File import kosong.']);
        }

        return [$rows, $this->mapHeaders($rows[0])];
    }

    /** @param array<int, mixed> $headers
     * @return array<int, string>
     */
    private function mapHeaders(array $headers): array
    {
        $knownHeaders = EmployeeImportColumns::normalizedHeaderMap();
        $columnMap = [];
        $foundKeys = [];
        $unsupported = [];

        foreach ($headers as $index => $header) {
            $normalized = $this->lookupKey($this->stringValue($header));

            if ($normalized === '') {
                continue;
            }

            if (! isset($knownHeaders[$normalized])) {
                $unsupported[] = $this->stringValue($header);

                continue;
            }

            $key = $knownHeaders[$normalized];
            if (isset($foundKeys[$key])) {
                throw ValidationException::withMessages(['file' => "Header {$header} muncul lebih dari satu kali."]);
            }

            $foundKeys[$key] = true;
            $columnMap[$index] = $key;
        }

        $missing = array_diff(array_keys(EmployeeImportColumns::DEFINITIONS), array_keys($foundKeys));

        if ($unsupported !== [] || $missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'Struktur kolom Excel tidak sesuai template. Download dan gunakan template terbaru.',
            ]);
        }

        return $columnMap;
    }

    /** @param array<int, mixed> $row
     * @param  array<int, string>  $columnMap
     * @param  Collection<string, Institution>  $institutions
     * @param  Collection<string, Position>  $positions
     * @return array<string, mixed>
     */
    private function prepareRow(array $row, array $columnMap, $institutions, $positions): array
    {
        $values = [];

        foreach ($columnMap as $index => $key) {
            $values[$key] = $this->stringValue($row[$index] ?? null);
        }

        $values['employee_number'] = $values['employee_number'] === '' ? null : $values['employee_number'];
        $values['personal_email'] = $values['personal_email'] === '' ? null : mb_strtolower($values['personal_email']);
        $values['login_email'] = mb_strtolower($values['login_email']);
        $values['join_date'] = $this->normalizeDate($row[array_search('join_date', $columnMap, true)] ?? null);
        $values['employee_type'] = EmployeeImportColumns::normalizeChoice($values['employee_type'], EmployeeImportColumns::EMPLOYEE_TYPES);
        $values['employment_status'] = EmployeeImportColumns::normalizeChoice($values['employment_status'], EmployeeImportColumns::EMPLOYMENT_STATUSES);

        $validator = Validator::make($values, [
            'employee_number' => ['nullable', 'regex:/^\d{10}$/'],
            'full_name' => ['required', 'string', 'max:255'],
            'login_email' => ['required', 'email', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'institution_name' => ['required', 'string', 'max:255'],
            'position_name' => ['required', 'string', 'max:255'],
            'employee_type' => ['required', 'string'],
            'employment_status' => ['required', 'string'],
            'join_date' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'employee_number.regex' => 'NUP harus tepat 10 digit angka.',
            'required' => ':attribute wajib diisi.',
            'email' => ':attribute harus berupa email yang valid.',
            'join_date.date_format' => 'Tanggal Masuk harus memakai format YYYY-MM-DD.',
        ], [
            'full_name' => 'Nama Lengkap',
            'login_email' => 'Email Login',
            'personal_email' => 'Email Pribadi',
            'institution_name' => 'Unit Kerja',
            'position_name' => 'Jabatan',
            'employee_type' => 'Jenis Pegawai',
            'employment_status' => 'Status Kerja',
            'join_date' => 'Tanggal Masuk',
        ]);

        if ($validator->fails()) {
            throw new EmployeeImportRowException($validator->errors()->first());
        }

        if ($values['employee_number'] !== null && in_array($values['employment_status'], ['nonaktif', 'resign'], true)) {
            throw new EmployeeImportRowException('Pegawai dengan NUP dan status kerja nonaktif/resign tidak memenuhi syarat QR aktif.');
        }

        $institution = $institutions->get($this->lookupKey($values['institution_name']));
        if (! $institution) {
            throw new EmployeeImportRowException('Unit Kerja tidak ditemukan atau tidak aktif.');
        }

        $position = $positions->get($institution->id.'|'.$this->lookupKey($values['position_name']));
        if (! $position) {
            throw new EmployeeImportRowException('Jabatan tidak ditemukan pada Unit Kerja yang dipilih atau tidak aktif.');
        }

        return $values + [
            'institution_id' => $institution->id,
            'position_id' => $position->id,
        ];
    }

    /** @param array<string, mixed> $row */
    private function assertNoDatabaseConflict(array $row): void
    {
        if ($row['employee_number'] !== null && Employee::query()->where('employee_number', $row['employee_number'])->exists()) {
            throw new EmployeeImportRowException('NUP sudah terdaftar dan data dilewati.', true);
        }

        if (User::query()->where('email', $row['login_email'])->exists()
            || EmployeeInvitation::query()->where('email', $row['login_email'])->where('status', 'unused')->exists()) {
            throw new EmployeeImportRowException('Email Login sudah digunakan atau memiliki undangan aktif.', true);
        }

        if ($row['personal_email'] !== null && Employee::query()->where('email', $row['personal_email'])->exists()) {
            throw new EmployeeImportRowException('Email Pribadi sudah digunakan pegawai lain.', true);
        }
    }

    private function generateInvitationCode(): string
    {
        do {
            $code = 'YAPISTA-REG-'.Str::upper(Str::random(8));
        } while (EmployeeInvitation::query()->where('invitation_code', $code)->exists());

        return $code;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return SpreadsheetDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return $this->stringValue($value);
            }
        }

        $string = $this->stringValue($value);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $string);
            if ($date !== false && $date->format($format) === $string) {
                return $date->format('Y-m-d');
            }
        }

        return $string;
    }

    private function stringValue(mixed $value): string
    {
        if (is_float($value) && floor($value) === $value) {
            return number_format($value, 0, '.', '');
        }

        return trim((string) $value);
    }

    /** @param array<int, mixed> $row */
    private function rowIsEmpty(array $row): bool
    {
        return collect($row)->every(fn (mixed $value): bool => $this->stringValue($value) === '');
    }

    private function lookupKey(string $value): string
    {
        $value = ltrim($value, "\xEF\xBB\xBF");

        return mb_strtolower(preg_replace('/\s+/', ' ', trim($value)) ?? '');
    }
}
