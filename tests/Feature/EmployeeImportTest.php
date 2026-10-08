<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeInvitation;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeQrTokenService;
use App\Support\Imports\EmployeeImportColumns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Institution $institution;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $this->institution = Institution::create([
            'name' => 'SMK Ibnu Sina',
            'level' => 'SMK',
            'status' => 'active',
        ]);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Guru',
            'type' => 'fungsional',
            'status' => 'active',
        ]);
    }

    public function test_authorized_admin_sees_reusable_import_button_and_modal(): void
    {
        $this->actingAs($this->admin)
            ->get(route('employees.index', absolute: false))
            ->assertOk()
            ->assertSee('Import Excel')
            ->assertSee('Import Data Pegawai')
            ->assertSee('Unduh Template')
            ->assertSee('name="file"', false)
            ->assertSee('accept=".xlsx,.xls,.csv"', false)
            ->assertSee('ti-file-import', false)
            ->assertSee('Email Login')
            ->assertSee('Import pegawai tidak otomatis membuat akun login.')
            ->assertSee('Akun dapat dibuat melalui Manajemen User');
    }

    public function test_import_routes_are_protected_from_unauthorized_roles_and_guests(): void
    {
        foreach (['panitia', 'pegawai'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)
                ->get(route('employees.import.template', absolute: false))
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('employees.import.store', absolute: false))
                ->assertForbidden();
        }

        $this->app['auth']->forgetGuards();
        $this->get(route('employees.import.template', absolute: false))->assertRedirect(route('login', absolute: false));
    }

    public function test_hr_admin_can_download_template_with_headers_matching_importer(): void
    {
        $hr = User::factory()->create(['role' => 'hr_admin', 'status' => 'active']);
        $response = $this->actingAs($hr)->get(route('employees.import.template', absolute: false));

        $response->assertOk()->assertDownload('template-import-pegawai.xlsx');
        $path = $this->temporaryPath('xlsx');
        file_put_contents($path, $response->streamedContent());
        $spreadsheet = IOFactory::load($path);
        $headers = array_slice($spreadsheet->getSheetByName('Data Pegawai')->toArray()[0], 0, count(EmployeeImportColumns::headers()));

        $this->assertSame(EmployeeImportColumns::headers(), $headers);
        $this->assertNotContains('NIK', $headers);
        $this->assertNotContains('Password', $headers);
        $this->assertNotContains('QR Token', $headers);
        $this->assertSame('Petunjuk', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('@', $spreadsheet->getSheetByName('Data Pegawai')->getStyle('F2')->getNumberFormat()->getFormatCode());
        $spreadsheet->disconnectWorksheets();
        @unlink($path);
    }

    public function test_valid_xlsx_import_creates_verified_employee_without_account_or_invitation_and_qr(): void
    {
        $upload = $this->spreadsheetUpload('xlsx', [$this->validRow()]);

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), ['file' => $upload])
            ->assertRedirect(route('employees.index', absolute: false))
            ->assertSessionHas('import_summary', fn (array $summary): bool => $summary['created'] === 1
                && $summary['verified'] === 1
                && $summary['qr_tokens_created'] === 1
                && $summary['invitations_created'] === 1);

        $employee = Employee::where('employee_number', '7770923991')->firstOrFail();
        $this->assertSame('verified', $employee->verification_status);
        $this->assertSame($this->admin->id, $employee->verified_by);
        $this->assertNotNull($employee->verified_at);
        $this->assertTrue($employee->activeQrToken()->exists());
        $this->assertNull($employee->user_id);
        $this->assertSame(1, User::count());
        $this->assertSame(1, EmployeeInvitation::count());
        $this->assertNull($employee->nik);
        $this->assertNull($employee->getRawOriginal('nik_encrypted'));
    }

    public function test_employee_without_nup_is_imported_as_draft_without_qr(): void
    {
        $row = $this->validRow();
        $row[5] = '';
        $row[6] = '';

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), ['file' => $this->spreadsheetUpload('xlsx', [$row])])
            ->assertRedirect(route('employees.index', absolute: false));

        $employee = Employee::where('full_name', 'Pegawai Import')->firstOrFail();
        $this->assertNull($employee->employee_number);
        $this->assertSame('draft', $employee->verification_status);
        $this->assertFalse($employee->activeQrToken()->exists());
        $this->assertNull($employee->user_id);
        $this->assertSame(0, EmployeeInvitation::count());
    }

    public function test_import_without_login_email_succeeds_without_creating_user_or_invitation(): void
    {
        $row = $this->validRow();
        $row[6] = '';

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), ['file' => $this->spreadsheetUpload('xlsx', [$row])])
            ->assertRedirect(route('employees.index', absolute: false))
            ->assertSessionHas('import_summary', fn (array $summary): bool => $summary['created'] === 1
                && $summary['failed'] === 0
                && $summary['invitations_created'] === 0);

        $employee = Employee::where('employee_number', '7770923991')->firstOrFail();
        $this->assertNull($employee->user_id);
        $this->assertSame(1, User::count());
        $this->assertSame(0, EmployeeInvitation::count());
    }

    public function test_xls_and_csv_files_are_supported(): void
    {
        $this->withoutExceptionHandling();

        foreach (['xls', 'csv'] as $index => $extension) {
            $row = $this->validRow();
            $row[5] = '77709239'.str_pad((string) ($index + 92), 2, '0', STR_PAD_LEFT);
            $row[0] = 'Pegawai '.strtoupper($extension);
            $row[6] = "pegawai.{$extension}@yapista.test";
            $row[7] = "pribadi.{$extension}@yapista.test";

            $this->actingAs($this->admin)
                ->post(route('employees.import.store', absolute: false), ['file' => $this->spreadsheetUpload($extension, [$row])])
                ->assertRedirect(route('employees.index', absolute: false));
        }

        $this->assertDatabaseHas('employees', ['full_name' => 'Pegawai XLS', 'verification_status' => 'verified']);
        $this->assertDatabaseHas('employees', ['full_name' => 'Pegawai CSV', 'verification_status' => 'verified']);
    }

    public function test_invalid_file_and_invalid_headers_are_rejected_with_clear_errors(): void
    {
        $this->actingAs($this->admin)
            ->from(route('employees.index', absolute: false))
            ->post(route('employees.import.store', absolute: false), [
                'file' => UploadedFile::fake()->create('pegawai.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('employees.index', absolute: false))
            ->assertSessionHasErrors('file');

        $headers = EmployeeImportColumns::headers();
        $headers[0] = 'Nomor Rahasia';

        $this->actingAs($this->admin)
            ->from(route('employees.index', absolute: false))
            ->post(route('employees.import.store', absolute: false), [
                'file' => $this->spreadsheetUpload('xlsx', [$this->validRow()], $headers),
            ])
            ->assertRedirect(route('employees.index', absolute: false))
            ->assertSessionHasErrors(['file' => 'Struktur kolom Excel tidak sesuai template. Unduh dan gunakan template terbaru.']);
    }

    public function test_duplicate_nup_is_skipped_without_changing_existing_qr(): void
    {
        $employee = Employee::create([
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'employee_number' => '7770923991',
            'full_name' => 'Pegawai Existing',
            'employee_type' => 'guru',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ]);
        $token = app(EmployeeQrTokenService::class)->generate($employee, $this->admin);

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), ['file' => $this->spreadsheetUpload('xlsx', [$this->validRow()])])
            ->assertSessionHas('import_summary', fn (array $summary): bool => $summary['created'] === 0
                && $summary['skipped'] === 1
                && str_contains($summary['errors'][0], 'NUP sudah terdaftar'));

        $this->assertSame(1, Employee::where('employee_number', '7770923991')->count());
        $this->assertSame($token->id, $employee->activeQrToken()->firstOrFail()->id);
    }

    public function test_invalid_row_is_reported_without_importing_partial_data(): void
    {
        $row = $this->validRow();
        $row[1] = 'Unit Tidak Ada';

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), ['file' => $this->spreadsheetUpload('xlsx', [$row])])
            ->assertSessionHas('import_summary', fn (array $summary): bool => $summary['created'] === 0
                && $summary['failed'] === 1
                && str_contains($summary['errors'][0], 'Unit Kerja tidak ditemukan'));

        $this->assertDatabaseMissing('employees', ['full_name' => 'Pegawai Import']);
        $this->assertSame(0, EmployeeInvitation::count());
    }

    public function test_header_only_duplicate_header_and_privilege_columns_are_rejected_safely(): void
    {
        $this->actingAs($this->admin)
            ->from(route('employees.index', absolute: false))
            ->post(route('employees.import.store', absolute: false), [
                'file' => $this->spreadsheetUpload('xlsx', []),
            ])
            ->assertRedirect(route('employees.index', absolute: false))
            ->assertSessionHasErrors(['file' => 'File tidak berisi data pegawai untuk diimport.']);

        $duplicateHeaders = EmployeeImportColumns::headers();
        $duplicateHeaders[1] = $duplicateHeaders[0];

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), [
                'file' => $this->spreadsheetUpload('xlsx', [$this->validRow()], $duplicateHeaders),
            ])
            ->assertSessionHasErrors('file');

        foreach (['Role', 'Verification Status', 'QR Token', 'Password', 'NIK Lookup'] as $privilegeHeader) {
            $headers = [...EmployeeImportColumns::headers(), $privilegeHeader];
            $row = [...$this->validRow(), 'super_admin'];

            $this->actingAs($this->admin)
                ->post(route('employees.import.store', absolute: false), [
                    'file' => $this->spreadsheetUpload('xlsx', [$row], $headers),
                ])
                ->assertSessionHasErrors(['file' => 'Struktur kolom Excel tidak sesuai template. Unduh dan gunakan template terbaru.']);
        }

        $this->assertDatabaseCount('employees', 0);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_blank_rows_are_ignored_and_duplicate_rows_do_not_create_orphan_accounts(): void
    {
        $first = $this->validRow();
        $duplicate = $this->validRow();
        $duplicate[0] = 'Duplikat Dalam File';
        $duplicate[6] = 'duplikat-file@yapista.test';
        $duplicate[7] = 'duplikat-pribadi@yapista.test';

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), [
                'file' => $this->spreadsheetUpload('xlsx', [array_fill(0, 8, ''), $first, $duplicate]),
            ])
            ->assertSessionHas('import_summary', fn (array $summary): bool => $summary['processed'] === 2
                && $summary['created'] === 1
                && $summary['skipped'] === 1);

        $this->assertSame(1, Employee::where('employee_number', '7770923991')->count());
        $this->assertDatabaseMissing('employees', ['full_name' => 'Duplikat Dalam File']);
        $this->assertSame(1, EmployeeInvitation::count());
    }

    public function test_import_handles_one_hundred_valid_rows_without_duplicate_state(): void
    {
        $rows = [];

        for ($index = 1; $index <= 100; $index++) {
            $row = $this->validRow();
            $row[5] = (string) (7_770_970_000 + $index);
            $row[0] = "Pegawai Smoke {$index}";
            $row[6] = "pegawai.smoke.{$index}@yapista.test";
            $row[7] = "pribadi.smoke.{$index}@yapista.test";
            $rows[] = $row;
        }

        $this->actingAs($this->admin)
            ->post(route('employees.import.store', absolute: false), [
                'file' => $this->spreadsheetUpload('xlsx', $rows),
            ])
            ->assertSessionHas('import_summary', fn (array $summary): bool => $summary['processed'] === 100
                && $summary['created'] === 100
                && $summary['failed'] === 0
                && $summary['skipped'] === 0
                && $summary['qr_tokens_created'] === 100);

        $this->assertDatabaseCount('employees', 100);
        $this->assertDatabaseCount('employee_invitations', 100);
        $this->assertSame(100, Employee::query()->whereHas('activeQrToken')->count());
    }

    /** @return list<string> */
    private function validRow(): array
    {
        return [
            'Pegawai Import',
            $this->institution->name,
            $this->position->name,
            'Guru',
            'Aktif',
            '7770923991',
            'pegawai.import@yapista.test',
            'pribadi.import@yapista.test',
            '2026-08-01',
        ];
    }

    /** @param list<list<string>> $rows
     * @param  list<string>|null  $headers
     */
    private function spreadsheetUpload(string $extension, array $rows, ?array $headers = null): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(array_merge([$headers ?? EmployeeImportColumns::headers()], $rows));
        $path = $this->temporaryPath($extension);

        $writer = match ($extension) {
            'xls' => new Xls($spreadsheet),
            'csv' => new Csv($spreadsheet),
            default => new Xlsx($spreadsheet),
        };
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        $mime = match ($extension) {
            'xls' => 'application/vnd.ms-excel',
            'csv' => 'text/csv',
            default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };

        return new UploadedFile($path, "pegawai.{$extension}", $mime, null, true);
    }

    private function temporaryPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'yapista-import-');
        $target = $path.'.'.$extension;
        rename($path, $target);

        return $target;
    }
}
