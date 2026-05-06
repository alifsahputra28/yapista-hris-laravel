<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_hr_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'hr_admin',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_panitia_can_access_scanner_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'panitia',
        ]);

        $this->actingAs($user)
            ->get('/scanner/dashboard')
            ->assertOk();
    }

    public function test_pegawai_can_access_pegawai_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $this->actingAs($user)
            ->get('/pegawai/dashboard')
            ->assertOk();
    }

    public function test_user_can_not_access_dashboard_for_another_role(): void
    {
        $user = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_inactive_user_is_logged_out_from_protected_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
