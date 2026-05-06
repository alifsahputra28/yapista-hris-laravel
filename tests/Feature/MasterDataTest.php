<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_institutions(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->get('/institutions')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/institutions', [
                'name' => 'MI Ibnu Sina',
                'level' => 'SD',
                'address' => 'Jl. Pendidikan',
                'status' => 'active',
            ])
            ->assertRedirect(route('institutions.index', absolute: false));

        $institution = Institution::where('name', 'MI Ibnu Sina')->firstOrFail();

        $this->actingAs($admin)
            ->put("/institutions/{$institution->id}", [
                'name' => 'MI Ibnu Sina Terpadu',
                'level' => 'SD',
                'address' => 'Jl. Pendidikan 2',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('institutions.index', absolute: false));

        $this->assertDatabaseHas('institutions', [
            'name' => 'MI Ibnu Sina Terpadu',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete("/institutions/{$institution->id}")
            ->assertRedirect(route('institutions.index', absolute: false));

        $this->assertDatabaseMissing('institutions', [
            'id' => $institution->id,
        ]);
    }

    public function test_hr_admin_can_manage_positions(): void
    {
        $admin = User::factory()->create([
            'role' => 'hr_admin',
        ]);
        $institution = Institution::create([
            'name' => 'SMP Ibnu Sina',
            'level' => 'SMP',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/positions')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/positions', [
                'institution_id' => $institution->id,
                'name' => 'Guru',
                'type' => 'fungsional',
                'status' => 'active',
            ])
            ->assertRedirect(route('positions.index', absolute: false));

        $position = Position::where('name', 'Guru')->firstOrFail();

        $this->assertSame($institution->id, $position->institution_id);

        $this->actingAs($admin)
            ->put("/positions/{$position->id}", [
                'institution_id' => $institution->id,
                'name' => 'Guru Produktif',
                'type' => 'fungsional',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('positions.index', absolute: false));

        $this->assertDatabaseHas('positions', [
            'name' => 'Guru Produktif',
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->delete("/positions/{$position->id}")
            ->assertRedirect(route('positions.index', absolute: false));

        $this->assertDatabaseMissing('positions', [
            'id' => $position->id,
        ]);
    }

    public function test_non_admin_roles_can_not_access_master_data(): void
    {
        foreach (['panitia', 'pegawai'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get('/institutions')
                ->assertForbidden();

            $this->actingAs($user)
                ->get('/positions')
                ->assertForbidden();
        }
    }
}
