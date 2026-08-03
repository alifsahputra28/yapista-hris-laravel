<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Support\Reports\SimpleXlsxWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XlsxExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Institution $institution;

    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $this->institution = Institution::create(['name' => 'Unit Export', 'level' => 'Unit', 'status' => 'active']);
        $this->position = Position::create([
            'institution_id' => $this->institution->id,
            'name' => 'Jabatan Export',
            'type' => 'administratif',
            'status' => 'active',
        ]);
    }

    public function test_simple_writer_creates_valid_minimum_xlsx_and_treats_formula_as_text(): void
    {
        $special = 'R&D <Tim> "Inti" O\'Brien ✓';
        $formulaValues = [
            '=HYPERLINK("https://example.test","Klik")',
            '+SUM(1,1)',
            '-10+20',
            '@SUM(1,1)',
        ];
        $rows = [[$special, $formulaValues[0]]];

        foreach (array_slice($formulaValues, 1) as $formulaValue) {
            $rows[] = ['Formula', $formulaValue];
        }

        $body = SimpleXlsxWriter::make(['Nama', 'Nilai'], $rows, 'Laporan & Uji');
        $files = $this->xlsxFiles($body);

        $this->assertNotSame('', $body);
        $this->assertSame(['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/worksheets/sheet1.xml'], array_keys($files));

        $worksheet = simplexml_load_string($files['xl/worksheets/sheet1.xml']);
        $this->assertNotFalse($worksheet);
        $worksheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $values = array_map(fn ($node): string => (string) $node, $worksheet->xpath('//x:t'));

        $this->assertContains('Nama', $values);
        $this->assertContains($special, $values);
        foreach ($formulaValues as $formulaValue) {
            $this->assertContains($formulaValue, $values);
        }
        $this->assertStringNotContainsString('<f>', $files['xl/worksheets/sheet1.xml']);
        $this->assertSame(10, substr_count($files['xl/worksheets/sheet1.xml'], 't="inlineStr"'));
    }

    public function test_employee_export_is_filtered_and_uses_only_employee_number_column(): void
    {
        $included = $this->employee('Pegawai Export Khusus', '7770950001');
        $this->employee('Pegawai Tidak Masuk', '7770950002');

        $response = $this->actingAs($this->admin)->get(route('reports.employees.export', [
            'search' => 'Export Khusus',
        ], absolute: false));

        $response->assertOk()->assertDownload();
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('Content-Disposition'));

        $values = $this->worksheetValues($response->streamedContent());
        $this->assertContains($included->full_name, $values);
        $this->assertContains('7770950001', $values);
        $this->assertNotContains('Pegawai Tidak Masuk', $values);
        $this->assertSame(1, count(array_filter($values, fn (string $value): bool => $value === 'NUP / Nomor Pegawai')));
        $this->assertNotContains('No. Buku', $values);
    }

    public function test_event_and_attendance_exports_exclude_cancelled_participants(): void
    {
        $event = Event::create([
            'name' => 'Event Export & Rekap',
            'event_date' => now()->toDateString(),
            'target_type' => 'all',
            'status' => 'closed',
            'created_by' => $this->admin->id,
        ]);
        $active = $this->employee('Active Export', '7770950003');
        $cancelled = $this->employee('Cancelled Export', '7770950004');
        $absent = $this->employee('Absent Export', '7770950005');
        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $active->id, 'participant_status' => 'invited']);
        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $cancelled->id, 'participant_status' => 'cancelled']);
        EventParticipant::create(['event_id' => $event->id, 'employee_id' => $absent->id, 'participant_status' => 'invited']);
        $this->attendance($event, $active);
        $this->attendance($event, $cancelled);

        $eventsResponse = $this->actingAs($this->admin)->get(route('reports.events.export', [
            'search' => 'Event Export',
        ], absolute: false));
        $eventsResponse->assertOk()->assertDownload();
        $eventRows = $this->worksheetRows($eventsResponse->streamedContent());
        $this->assertSame('Total Peserta Aktif', $eventRows[0][7]);
        $this->assertSame('2', $eventRows[1][7]);
        $this->assertSame('1', $eventRows[1][8]);
        $this->assertSame('1', $eventRows[1][9]);
        $this->assertSame('50%', $eventRows[1][10]);

        $attendanceResponse = $this->actingAs($this->admin)->get(route('reports.events.attendances.export', $event, absolute: false));
        $attendanceResponse->assertOk()->assertDownload();
        $attendanceValues = $this->worksheetValues($attendanceResponse->streamedContent());
        $this->assertContains('Active Export', $attendanceValues);
        $this->assertContains('Absent Export', $attendanceValues);
        $this->assertContains('7770950003', $attendanceValues);
        $this->assertNotContains('Cancelled Export', $attendanceValues);
        $this->assertNotContains('7770950004', $attendanceValues);

        $attendanceRows = $this->worksheetRows($attendanceResponse->streamedContent());
        $absentRow = collect($attendanceRows)->first(fn (array $row): bool => in_array('Absent Export', $row, true));
        $this->assertIsArray($absentRow);
        $this->assertSame('Belum Hadir', $absentRow[5]);
        $this->assertSame('', $absentRow[6]);
        $this->assertSame('', $absentRow[7]);
        $this->assertSame('', $absentRow[8]);
    }

    private function employee(string $name, string $employeeNumber): Employee
    {
        return Employee::create([
            'institution_id' => $this->institution->id,
            'position_id' => $this->position->id,
            'employee_number' => $employeeNumber,
            'full_name' => $name,
            'email' => str($name)->slug().uniqid().'@yapista.test',
            'phone' => '081234567890',
            'employee_type' => 'tenaga_kependidikan',
            'employment_status' => 'aktif',
            'verification_status' => 'verified',
        ]);
    }

    private function attendance(Event $event, Employee $employee): EventAttendance
    {
        return EventAttendance::create([
            'event_id' => $event->id,
            'employee_id' => $employee->id,
            'scanned_by' => $this->admin->id,
            'scanned_at' => now(),
            'attendance_status' => 'present',
            'scan_method' => 'barcode',
        ]);
    }

    /** @return array<string, string> */
    private function xlsxFiles(string $body): array
    {
        $files = [];
        $offset = 0;

        while (substr($body, $offset, 4) === "PK\x03\x04") {
            $header = unpack(
                'Vsignature/vversion/vflags/vcompression/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vnameLength/vextraLength',
                substr($body, $offset, 30)
            );
            $this->assertIsArray($header);
            $this->assertSame(0, $header['compression']);
            $nameOffset = $offset + 30;
            $name = substr($body, $nameOffset, $header['nameLength']);
            $contentOffset = $nameOffset + $header['nameLength'] + $header['extraLength'];
            $files[$name] = substr($body, $contentOffset, $header['compressed']);
            $offset = $contentOffset + $header['compressed'];
        }

        $this->assertStringStartsWith("PK\x01\x02", substr($body, $offset, 4));

        return $files;
    }

    /** @return array<int, string> */
    private function worksheetValues(string $body): array
    {
        return collect($this->worksheetRows($body))->flatten()->all();
    }

    /** @return array<int, array<int, string>> */
    private function worksheetRows(string $body): array
    {
        $files = $this->xlsxFiles($body);
        $worksheet = simplexml_load_string($files['xl/worksheets/sheet1.xml']);
        $this->assertNotFalse($worksheet);
        $worksheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return array_map(function ($row): array {
            $row->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            return array_map(fn ($cell): string => (string) ($cell->is->t ?? ''), $row->xpath('./x:c'));
        }, $worksheet->xpath('//x:row'));
    }
}
