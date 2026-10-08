<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_page_is_super_admin_only_and_exposes_both_canonical_masters(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('options.index', absolute: false))
            ->assertOk()
            ->assertSee('Level Unit Kerja')
            ->assertSee('U1')
            ->assertSee('U5')
            ->assertSee('Tipe Jabatan')
            ->assertSee('ORG')
            ->assertSee('OPS');

        foreach (['hr_admin', 'panitia', 'pegawai'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($user)->get(route('options.index', absolute: false))->assertForbidden();
        }
    }

    public function test_unit_and_position_forms_accept_only_canonical_codes(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);

        $this->actingAs($admin)->post(route('institutions.store', absolute: false), [
            'name' => 'Unit Invalid Level', 'level' => 'Yayasan', 'status' => 'active',
        ])->assertSessionHasErrors('level');

        $unit = Institution::create(['name' => 'Unit Canonical', 'level' => 'U2', 'status' => 'active']);
        $this->actingAs($admin)->post(route('positions.store', absolute: false), [
            'institution_id' => $unit->id, 'name' => 'Jabatan Invalid Type', 'type' => 'teknis', 'status' => 'active',
        ])->assertSessionHasErrors('type');

        $this->actingAs($admin)->post(route('institutions.store', absolute: false), [
            'name' => 'Unit Canonical New', 'level' => 'U3', 'status' => 'active',
        ])->assertRedirect(route('institutions.index', absolute: false));

        $this->actingAs($admin)->post(route('positions.store', absolute: false), [
            'institution_id' => $unit->id, 'name' => 'Jabatan Canonical', 'type' => 'STR', 'status' => 'active',
        ])->assertRedirect(route('positions.index', absolute: false));

        $this->assertDatabaseHas('institutions', ['name' => 'Unit Canonical New', 'level' => 'U3']);
        $this->assertDatabaseHas('positions', ['name' => 'Jabatan Canonical', 'type' => 'STR']);
    }

    public function test_legacy_values_are_displayed_with_deterministic_compatibility_labels(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
        $unit = Institution::create(['name' => 'Legacy Yayasan', 'level' => 'Yayasan', 'status' => 'active']);
        Position::create(['institution_id' => $unit->id, 'name' => 'Staff IT', 'type' => 'teknis', 'status' => 'active']);
        Position::create(['institution_id' => $unit->id, 'name' => 'Staff Sarpras', 'type' => 'teknis', 'status' => 'active']);

        $this->actingAs($admin)->get(route('institutions.index', absolute: false))
            ->assertSee('U1 — Induk / Yayasan');
        $this->actingAs($admin)->get(route('positions.index', absolute: false))
            ->assertSee('ADM — Administratif / Pelaksana')
            ->assertSee('OPS — Operasional / Pendukung');
    }
}
