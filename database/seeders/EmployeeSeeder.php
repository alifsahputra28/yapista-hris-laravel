<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class EmployeeSeeder extends Seeder
{
    /** @var list<string> */
    private const EMPLOYEE_TYPES = [
        'guru',
        'dosen',
        'tenaga_kependidikan',
        'staff_yayasan',
        'security',
        'cleaning_service',
        'driver',
        'teknisi',
    ];

    /** @var list<string> */
    private const EMPLOYMENT_STATUSES = [
        'aktif',
        'kontrak',
        'honorer',
        'part_time',
        'nonaktif',
        'resign',
    ];

    public function run(): void
    {
        $dataFile = database_path('seeders/data/employees.php');
        $rows = require $dataFile;

        if (! is_array($rows)) {
            throw new RuntimeException("File data pegawai {$dataFile} harus mengembalikan array.");
        }

        $summary = $this->seedRows($rows);

        if ($this->command) {
            $this->command->info("Existing employees created: {$summary['existing_employees_created']}");
            $this->command->info("Existing employees updated: {$summary['existing_employees_updated']}");
            $this->command->info("New employees created: {$summary['new_employees_created']}");
            $this->command->info("Promoted to verified: {$summary['promoted_to_verified']}");
            $this->command->info("QR tokens created: {$summary['qr_tokens_created']}");
            $this->command->info("QR tokens preserved: {$summary['qr_tokens_preserved']}");
            $this->command->info("Users created: {$summary['users_created']}");
            $this->command->info("Users preserved: {$summary['users_preserved']}");
        }
    }

    /**
     * Seed pre-validated onboarding rows. This public entry point also lets tests
     * exercise invalid input without replacing the real data file.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     existing_employees_created: int,
     *     existing_employees_updated: int,
     *     new_employees_created: int,
     *     promoted_to_verified: int,
     *     qr_tokens_created: int,
     *     qr_tokens_preserved: int,
     *     users_created: int,
     *     users_preserved: int
     * }
     */
    public function seedRows(array $rows): array
    {
        $preparedRows = $this->validateAndPrepareRows($rows);
        $defaultPassword = $this->resolveDefaultPassword();
        $qrTokenService = app(EmployeeQrTokenService::class);
        $qrCreator = User::query()->where('email', 'admin@yapista.test')->first();

        $this->assertNoDatabaseConflicts($preparedRows);

        return DB::transaction(function () use ($preparedRows, $defaultPassword, $qrTokenService, $qrCreator): array {
            $summary = [
                'existing_employees_created' => 0,
                'existing_employees_updated' => 0,
                'new_employees_created' => 0,
                'promoted_to_verified' => 0,
                'qr_tokens_created' => 0,
                'qr_tokens_preserved' => 0,
                'users_created' => 0,
                'users_preserved' => 0,
            ];

            foreach ($preparedRows as $row) {
                $user = User::firstOrCreate(
                    ['email' => $row['login_email']],
                    [
                        'name' => $row['full_name'],
                        'password' => Hash::make($row['temporary_password'] ?? $defaultPassword),
                        'role' => 'pegawai',
                        'status' => 'active',
                    ],
                );

                $summary[$user->wasRecentlyCreated ? 'users_created' : 'users_preserved']++;

                $user->fill([
                    'name' => $row['full_name'],
                    'role' => 'pegawai',
                    'status' => 'active',
                ])->save();

                $employeeByNumber = $row['employee_number'] !== null
                    ? Employee::query()->where('employee_number', $row['employee_number'])->first()
                    : null;
                $employeeByUser = Employee::query()->where('user_id', $user->id)->first();
                $employee = $employeeByNumber ?? $employeeByUser;
                $masterData = [
                    'user_id' => $user->id,
                    'institution_id' => $row['institution_id'],
                    'position_id' => $row['position_id'],
                    'full_name' => $row['full_name'],
                    'employee_type' => $row['employee_type'],
                    'employment_status' => $row['employment_status'],
                ];

                if (filled($row['join_date'] ?? null)) {
                    $masterData['join_date'] = $row['join_date'];
                }

                if (filled($row['personal_email'] ?? null)) {
                    $masterData['email'] = $row['personal_email'];
                }

                if ($employee) {
                    $wasVerified = $employee->isVerified();

                    if ($row['employee_number'] !== null) {
                        $masterData['employee_number'] = $row['employee_number'];
                        $masterData['verification_status'] = 'verified';
                    }

                    $employee->fill($masterData)->save();
                    if ($row['employee_number'] !== null) {
                        $summary['existing_employees_updated']++;
                        if (! $wasVerified) {
                            $summary['promoted_to_verified']++;
                        }
                    }
                } else {
                    $isExistingEmployee = $row['employee_number'] !== null;
                    $employee = Employee::create($masterData + [
                        'employee_number' => $row['employee_number'],
                        'email' => $row['personal_email'] ?? null,
                        'join_date' => $row['join_date'] ?? null,
                        'verification_status' => $isExistingEmployee ? 'verified' : 'draft',
                    ]);
                    $summary[$isExistingEmployee ? 'existing_employees_created' : 'new_employees_created']++;
                }

                if ($employee->isVerified() && $employee->hasValidEmployeeNumber()) {
                    $hadActiveToken = $employee->activeQrToken()->exists();
                    $qrTokenService->generate($employee, $qrCreator);
                    $summary[$hadActiveToken ? 'qr_tokens_preserved' : 'qr_tokens_created']++;
                }
            }

            return $summary;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function validateAndPrepareRows(array $rows): array
    {
        $prepared = [];
        $employeeNumbers = [];
        $loginEmails = [];

        foreach (array_values($rows) as $index => $row) {
            $line = $index + 1;

            if (! is_array($row)) {
                throw new RuntimeException("Data pegawai baris {$line} harus berupa array.");
            }

            foreach (['full_name', 'login_email', 'institution_name', 'position_name', 'employee_type', 'employment_status'] as $field) {
                if (! isset($row[$field]) || ! is_string($row[$field]) || trim($row[$field]) === '') {
                    throw new RuntimeException("Data pegawai baris {$line}: {$field} wajib diisi.");
                }

                $row[$field] = trim($row[$field]);
            }

            $employeeNumber = $row['employee_number'] ?? null;
            $row['employee_number'] = is_string($employeeNumber) ? trim($employeeNumber) : $employeeNumber;
            $row['employee_number'] = $row['employee_number'] === '' ? null : $row['employee_number'];

            if ($row['employee_number'] !== null) {
                if (! is_string($row['employee_number']) || preg_match('/^\d{10}$/', $row['employee_number']) !== 1) {
                    throw new RuntimeException("Data pegawai baris {$line}: employee_number harus null atau tepat 10 digit angka.");
                }

                if (isset($employeeNumbers[$row['employee_number']])) {
                    throw new RuntimeException("Data pegawai baris {$line}: employee_number {$row['employee_number']} duplikat dalam file data.");
                }
                $employeeNumbers[$row['employee_number']] = true;
            }

            $row['login_email'] = mb_strtolower($row['login_email']);
            if (filter_var($row['login_email'], FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException("Data pegawai baris {$line}: login_email tidak valid.");
            }

            if (isset($loginEmails[$row['login_email']])) {
                throw new RuntimeException("Data pegawai baris {$line}: login_email {$row['login_email']} duplikat dalam file data.");
            }
            $loginEmails[$row['login_email']] = true;

            if (array_key_exists('personal_email', $row) && filled($row['personal_email'])) {
                $row['personal_email'] = mb_strtolower(trim((string) $row['personal_email']));

                if (filter_var($row['personal_email'], FILTER_VALIDATE_EMAIL) === false) {
                    throw new RuntimeException("Data pegawai baris {$line}: personal_email tidak valid.");
                }
            } else {
                $row['personal_email'] = null;
            }

            if (! in_array($row['employee_type'], self::EMPLOYEE_TYPES, true)) {
                throw new RuntimeException("Data pegawai baris {$line}: employee_type {$row['employee_type']} tidak valid.");
            }

            if (! in_array($row['employment_status'], self::EMPLOYMENT_STATUSES, true)) {
                throw new RuntimeException("Data pegawai baris {$line}: employment_status {$row['employment_status']} tidak valid.");
            }

            if (filled($row['join_date'] ?? null) && ! $this->isValidDate((string) $row['join_date'])) {
                throw new RuntimeException("Data pegawai baris {$line}: join_date harus memakai format YYYY-MM-DD dan merupakan tanggal valid.");
            }

            if (array_key_exists('temporary_password', $row) && (! is_string($row['temporary_password']) || trim($row['temporary_password']) === '')) {
                throw new RuntimeException("Data pegawai baris {$line}: temporary_password harus berupa string yang tidak kosong.");
            }

            $institutions = Institution::where('name', $row['institution_name'])->get();
            if ($institutions->count() !== 1) {
                throw new RuntimeException("Data pegawai baris {$line}: unit kerja {$row['institution_name']} tidak ditemukan secara unik.");
            }

            $positions = Position::where('institution_id', $institutions->first()->id)
                ->where('name', $row['position_name'])
                ->get();
            if ($positions->count() !== 1) {
                throw new RuntimeException("Data pegawai baris {$line}: jabatan {$row['position_name']} pada {$row['institution_name']} tidak ditemukan secara unik.");
            }

            $row['institution_id'] = $institutions->first()->id;
            $row['position_id'] = $positions->first()->id;
            $prepared[] = $row;
        }

        return $prepared;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function assertNoDatabaseConflicts(array $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 1;
            $employeeByNumber = $row['employee_number'] !== null
                ? Employee::query()->where('employee_number', $row['employee_number'])->first()
                : null;
            $user = User::where('email', $row['login_email'])->first();

            if ($user && ! $user->isPegawai()) {
                throw new RuntimeException("Data pegawai baris {$line}: login_email sudah digunakan akun dengan role {$user->role}.");
            }

            $employeeUsingUser = $user ? Employee::where('user_id', $user->id)->first() : null;
            if ($row['employee_number'] !== null
                && $employeeUsingUser
                && (($employeeByNumber && $employeeUsingUser->isNot($employeeByNumber))
                    || (! $employeeByNumber && $employeeUsingUser->employee_number !== null))) {
                throw new RuntimeException("Data pegawai baris {$line}: login_email sudah terhubung ke pegawai lain.");
            }

            if ($employeeByNumber?->user_id !== null && (! $user || $employeeByNumber->user_id !== $user->id)) {
                throw new RuntimeException("Data pegawai baris {$line}: employee_number sudah terhubung ke akun lain.");
            }
        }
    }

    private function resolveDefaultPassword(): string
    {
        $password = env('EMPLOYEE_SEED_DEFAULT_PASSWORD');

        if (blank($password) && app()->environment('production')) {
            throw new RuntimeException('EMPLOYEE_SEED_DEFAULT_PASSWORD wajib tersedia untuk proses onboarding di production.');
        }

        return filled($password) ? (string) $password : 'password';
    }

    private function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
