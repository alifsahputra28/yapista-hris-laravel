<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use App\Support\Imports\EmployeeImportColumns;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeWorkFieldsTest extends TestCase
{
    use RefreshDatabase;

    private Institution $unitA;

    private Institution $unitB;

    private Position $positionA;

    private Position $positionB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'hr_admin']));
        $this->unitA = Institution::create(['name' => 'Unit A', 'status' => 'active']);
        $this->unitB = Institution::create(['name' => 'Unit B', 'status' => 'active']);
        $this->positionA = Position::create(['name' => 'Jabatan A', 'institution_id' => $this->unitA->id, 'status' => 'active']);
        $this->positionB = Position::create(['name' => 'Jabatan B', 'institution_id' => $this->unitB->id, 'status' => 'active']);
    }

    public function test_create_starts_with_disabled_position_and_the_existing_eight_employee_types(): void
    {
        $response = $this->get(route('employees.create'))->assertOk();
        $position = $this->field($response->getContent(), 'position_id');
        $this->assertTrue($position->hasAttribute('disabled'));
        $this->assertSame([''], $this->values($position));
        $this->assertStringContainsString('Pilih unit kerja terlebih dahulu', $position->textContent);
        $this->assertStringContainsString('Cari atau pilih unit kerja', $this->field($response->getContent(), 'institution_id')->textContent);
        $this->assertSame(['', ...array_keys(EmployeeImportColumns::EMPLOYEE_TYPES)], $this->values($this->field($response->getContent(), 'employee_type')));
        $response->assertSee('assets/js/employee-work-fields.js', false);
    }

    public function test_edit_server_renders_only_its_unit_positions_and_preserves_existing_selection(): void
    {
        $employee = Employee::create($this->payload());
        $response = $this->get(route('employees.edit', $employee))->assertOk();
        $position = $this->field($response->getContent(), 'position_id');
        $this->assertFalse($position->hasAttribute('disabled'));
        $this->assertSame(['', (string) $this->positionA->id], $this->values($position));
        $this->assertSame((string) $this->positionA->id, $this->selected($position));
        $this->assertSame((string) $this->unitA->id, $this->selected($this->field($response->getContent(), 'institution_id')));
        $this->assertSame('guru', $this->selected($this->field($response->getContent(), 'employee_type')));
    }

    public function test_edit_preserves_assigned_inactive_master_data_without_adding_it_to_create(): void
    {
        $employee = Employee::create($this->payload());
        $this->unitA->update(['status' => 'inactive']);
        $this->positionA->update(['status' => 'inactive']);
        $response = $this->get(route('employees.edit', $employee))->assertOk();
        $this->assertSame((string) $this->unitA->id, $this->selected($this->field($response->getContent(), 'institution_id')));
        $this->assertSame((string) $this->positionA->id, $this->selected($this->field($response->getContent(), 'position_id')));
        $create = $this->get(route('employees.create'))->assertOk();
        $this->assertNotContains((string) $this->unitA->id, $this->values($this->field($create->getContent(), 'institution_id')));
    }

    public function test_invalid_pair_is_rejected_and_old_unit_filters_positions_after_redirect(): void
    {
        $url = route('employees.create');
        $this->from($url)->post(route('employees.store'), $this->payload(['position_id' => $this->positionB->id]))
            ->assertRedirect($url)
            ->assertSessionHasErrors(['position_id' => 'Jabatan yang dipilih tidak sesuai dengan unit kerja.']);
        $response = $this->withCookie(config('session.cookie'), session()->getId())->get($url)->assertOk();
        $position = $this->field($response->getContent(), 'position_id');
        $this->assertSame(['', (string) $this->positionA->id], $this->values($position));
        $this->assertSame('', $this->selected($position));
        $this->assertSame('true', $position->getAttribute('aria-invalid'));
        $this->assertSame('position-help position_id-error', $position->getAttribute('aria-describedby'));
        $response->assertSee('id="position_id-error"', false);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_empty_unit_is_disabled_with_explanation_and_cannot_accept_other_unit_position(): void
    {
        $empty = Institution::create(['name' => 'Unit Kosong', 'status' => 'active']);
        $url = route('employees.create');
        $this->from($url)->post(route('employees.store'), $this->payload(['institution_id' => $empty->id]))
            ->assertSessionHasErrors('position_id');
        $response = $this->withCookie(config('session.cookie'), session()->getId())->get($url)->assertOk();
        $position = $this->field($response->getContent(), 'position_id');
        $this->assertTrue($position->hasAttribute('disabled'));
        $this->assertSame([''], $this->values($position));
        $this->assertStringContainsString('Belum ada jabatan pada unit kerja ini.', $position->textContent);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_valid_create_and_unit_change_save_but_stale_position_cannot_change_employee(): void
    {
        $this->post(route('employees.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('employees.index'));
        $employee = Employee::sole();
        $this->assertSame('guru', $employee->employee_type);
        $this->assertSame('draft', $employee->verification_status);
        $this->assertFalse($employee->activeQrToken()->exists());

        $this->put(route('employees.update', $employee), $this->payload(['institution_id' => $this->unitB->id]))
            ->assertSessionHasErrors('position_id');
        $this->assertSame($this->unitA->id, $employee->fresh()->institution_id);
        $this->assertSame($this->positionA->id, $employee->fresh()->position_id);

        $this->put(route('employees.update', $employee), $this->payload([
            'institution_id' => $this->unitB->id, 'position_id' => $this->positionB->id, 'employee_type' => 'dosen',
        ]))->assertRedirect(route('employees.index'));
        $employee->refresh();
        $this->assertSame($this->unitB->id, $employee->institution_id);
        $this->assertSame($this->positionB->id, $employee->position_id);
        $this->assertSame('dosen', $employee->employee_type);
        $this->get(route('employees.show', $employee))->assertOk();
    }

    public function test_required_and_invalid_type_messages_remain_indonesian(): void
    {
        $this->post(route('employees.store'), $this->payload(['institution_id' => '', 'position_id' => '', 'employee_type' => '']))
            ->assertSessionHasErrors([
                'institution_id' => 'Unit kerja wajib dipilih.',
                'position_id' => 'Jabatan wajib dipilih.',
                'employee_type' => 'Jenis pegawai wajib dipilih.',
            ]);
        $this->post(route('employees.store'), $this->payload(['employee_type' => 'invented-type']))
            ->assertSessionHasErrors(['employee_type' => 'Jenis pegawai yang dipilih tidak valid.']);
        $this->assertDatabaseCount('employees', 0);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Synthetic Work Fields', 'institution_id' => $this->unitA->id,
            'position_id' => $this->positionA->id, 'employee_type' => 'guru',
            'employment_status' => 'aktif', 'verification_status' => 'draft',
        ], $overrides);
    }

    private function field(string $html, string $id): DOMElement
    {
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $field = (new DOMXPath($document))->query('//select[@id="'.$id.'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $field);

        return $field;
    }

    private function values(DOMElement $field): array
    {
        return array_map(fn ($option) => $option->getAttribute('value'), iterator_to_array($field->getElementsByTagName('option')));
    }

    private function selected(DOMElement $field): string
    {
        foreach ($field->getElementsByTagName('option') as $option) {
            if ($option->hasAttribute('selected')) {
                return $option->getAttribute('value');
            }
        }

        return '';
    }
}
