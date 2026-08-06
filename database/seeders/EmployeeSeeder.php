<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
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
            $this->command->info("Pegawai dibuat: {$summary['employees_created']}");
            $this->command->info("Pegawai diperbarui: {$summary['employees_updated']}");
            $this->command->info("User dibuat: {$summary['users_created']}");
            $this->command->info("User existing: {$summary['users_existing']}");
        }
    }

    /**
     * Seed pre-validated onboarding rows. This public entry point also lets tests
     * exercise invalid input without replacing the real data file.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{employees_created: int, employees_updated: int, users_created: int, users_existing: int}
     */
    public function seedRows(array $rows): array
    {
        $preparedRows = $this->validateAndPrepareRows($rows);
        $defaultPassword = $this->resolveDefaultPassword();

        $this->assertNoDatabaseConflicts($preparedRows);

        return DB::transaction(function () use ($preparedRows, $defaultPassword): array {
            $summary = [
                'employees_created' => 0,
                'employees_updated' => 0,
                'users_created' => 0,
                'users_existing' => 0,
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

                $summary[$user->wasRecentlyCreated ? 'users_created' : 'users_existing']++;

                $user->fill([
                    'name' => $row['full_name'],
                    'role' => 'pegawai',
                    'status' => 'active',
                ])->save();

                $employee = Employee::where('employee_number', $row['employee_number'])->first();
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
                    $employee->fill($masterData)->save();
                    $summary['employees_updated']++;

                    continue;
                }

                Employee::create($masterData + [
                    'employee_number' => $row['employee_number'],
                    'email' => $row['personal_email'] ?? null,
                    'join_date' => $row['join_date'] ?? null,
                    'verification_status' => 'draft',
                ]);
                $summary['employees_created']++;
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

            foreach (['employee_number', 'full_name', 'login_email', 'institution_name', 'position_name', 'employee_type', 'employment_status'] as $field) {
                if (! isset($row[$field]) || ! is_string($row[$field]) || trim($row[$field]) === '') {
                    throw new RuntimeException("Data pegawai baris {$line}: {$field} wajib diisi.");
                }

                $row[$field] = trim($row[$field]);
            }

            if (preg_match('/^\d{10}$/', $row['employee_number']) !== 1) {
                throw new RuntimeException("Data pegawai baris {$line}: employee_number harus tepat 10 digit angka.");
            }

            if (isset($employeeNumbers[$row['employee_number']])) {
                throw new RuntimeException("Data pegawai baris {$line}: employee_number {$row['employee_number']} duplikat dalam file data.");
            }
            $employeeNumbers[$row['employee_number']] = true;

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
            $employee = Employee::where('employee_number', $row['employee_number'])->first();
            $user = User::where('email', $row['login_email'])->first();

            if ($user && ! $user->isPegawai()) {
                throw new RuntimeException("Data pegawai baris {$line}: login_email sudah digunakan akun dengan role {$user->role}.");
            }

            $employeeUsingUser = $user ? Employee::where('user_id', $user->id)->first() : null;
            if ($employeeUsingUser && (! $employee || $employeeUsingUser->isNot($employee))) {
                throw new RuntimeException("Data pegawai baris {$line}: login_email sudah terhubung ke pegawai lain.");
            }

            if ($employee?->user_id !== null && (! $user || $employee->user_id !== $user->id)) {
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
