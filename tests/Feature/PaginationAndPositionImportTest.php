<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PaginationAndPositionImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_and_position_lists_use_pagination_and_preserve_filters(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $institutions = [];
        foreach (range(1, 31) as $index) {
            $institutions[] = Institution::create([
                'name' => sprintf('Unit %02d', $index), 'level' => 'Unit', 'status' => 'active',
            ]);
        }

        foreach (range(1, 31) as $index) {
            Position::create([
                'institution_id' => $institutions[0]->id, 'name' => sprintf('Jabatan %02d', $index),
                'type' => 'administratif', 'status' => 'active',
            ]);
        }

        $this->actingAs($admin)->get(route('institutions.index', ['page' => 2], false))
            ->assertOk()->assertViewHas('institutions', fn ($items) => $items->currentPage() === 2 && $items->count() === 15)
            ->assertSee('16')->assertSee('30');

        $this->actingAs($admin)->get(route('positions.index', ['per_page' => 25, 'page' => 2], false))
            ->assertOk()->assertViewHas('positions', fn ($items) => $items->perPage() === 25 && $items->currentPage() === 2 && $items->count() === 6);

        $this->actingAs($admin)->get(route('positions.index', [
            'search' => 'Jabatan',
            'institution_id' => $institutions[0]->id,
            'per_page' => 15,
            'page' => 2,
        ], false))
            ->assertOk()
            ->assertViewHas('positions', fn ($items) => $items->currentPage() === 2 && $items->count() === 15)
            ->assertSee('search=Jabatan', false)
            ->assertSee('institution_id=1', false)
            ->assertSee('per_page=15', false);

        $this->actingAs($admin)->get(route('positions.index', ['per_page' => 9999], false))
            ->assertOk()->assertViewHas('positions', fn ($items) => $items->perPage() === 15);
    }

    public function test_super_admin_can_download_template_and_import_valid_rows_with_safe_summary(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $unit = Institution::create(['name' => 'TK Ibnu Sina', 'level' => 'TK', 'status' => 'active']);
        Position::create(['institution_id' => $unit->id, 'name' => 'Guru', 'type' => 'fungsional', 'status' => 'active']);

        $this->actingAs($admin)->get(route('positions.import.template', absolute: false))
            ->assertOk()->assertDownload('template-import-jabatan.xlsx');

        $validRows = array_map(
            fn (int $index): array => [sprintf('Jabatan Import %02d', $index), 'TK Ibnu Sina', 'administratif', 'active'],
            range(1, 16),
        );
        $upload = $this->spreadsheet([
            ...$validRows,
            ['Guru', 'TK Ibnu Sina', 'fungsional', 'active'],
            ['Jabatan Salah', 'Unit Tidak Ada', 'teknis', 'active'],
            ['Kategori Salah', 'TK Ibnu Sina', 'tidak-valid', 'active'],
            [null, null, null, null],
        ]);

        $this->actingAs($admin)->post(route('positions.import.store', absolute: false), ['file' => $upload])
            ->assertRedirect(route('positions.index', absolute: false))
            ->assertSessionHas('position_import_summary', fn (array $summary) => $summary['created'] === 16
                && $summary['skipped'] === 1 && $summary['failed'] === 2);

        $this->assertDatabaseHas('positions', ['institution_id' => $unit->id, 'name' => 'Jabatan Import 01']);
        $this->assertSame(1, Position::query()->where('institution_id', $unit->id)->where('name', 'Guru')->count());
        $this->assertDatabaseMissing('positions', ['name' => 'Kategori Salah']);

        $this->actingAs($admin)->get(route('positions.index', [
            'institution_id' => $unit->id,
            'page' => 2,
        ], false))
            ->assertOk()
            ->assertViewHas('positions', fn ($items) => $items->total() === 17
                && $items->currentPage() === 2 && $items->count() === 2);
    }

    public function test_position_import_is_super_admin_only_and_rejects_invalid_file_boundaries(): void
    {
        $hr = User::factory()->create(['role' => 'hr_admin', 'status' => 'active']);
        $this->actingAs($hr)->get(route('positions.import.template', absolute: false))->assertForbidden();
        $this->actingAs($hr)->post(route('positions.import.store', absolute: false), ['file' => $this->spreadsheet([])])->assertForbidden();

        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $this->actingAs($admin)->post(route('positions.import.store', absolute: false), [
            'file' => UploadedFile::fake()->create('jabatan.xlsx', 2050, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ])->assertSessionHasErrors('file');
        $this->actingAs($admin)->post(route('positions.import.store', absolute: false), [
            'file' => UploadedFile::fake()->create('jabatan.exe', 10, 'application/octet-stream'),
        ])->assertSessionHasErrors('file');
    }

    /** @param list<array<int, mixed>> $rows */
    private function spreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Nama Jabatan', 'Unit Kerja', 'Kategori', 'Status'], ...$rows,
        ]);
        $path = tempnam(sys_get_temp_dir(), 'position-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, 'jabatan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
