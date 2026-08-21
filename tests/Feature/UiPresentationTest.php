<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UiPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_recovery_uses_indonesian_mantis_presentation(): void
    {
        $this->get(route('password.request', absolute: false))
            ->assertOk()
            ->assertSee('Lupa Password')
            ->assertSee('auth-login-shell', escape: false)
            ->assertSee('lang="id"', escape: false)
            ->assertDontSee('Forgot your password?');
    }

    public function test_not_found_page_uses_branded_error_presentation(): void
    {
        $this->get('/halaman-yang-tidak-tersedia')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('YAPISTA HRIS')
            ->assertDontSee('Not Found');
    }

    public function test_non_employee_account_page_uses_mantis_layout(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('profile.edit', absolute: false))
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Informasi Akun')
            ->assertSee('pc-sidebar', escape: false)
            ->assertDontSee('Profile Information');
    }

    public function test_indonesian_authentication_and_validation_copy_is_available(): void
    {
        app()->setLocale('id');

        $validator = Validator::make([], [
            'full_name' => ['required'],
            'institution_id' => ['required'],
        ]);

        $this->assertSame('Email atau password tidak sesuai.', __('auth.failed'));
        $this->assertSame('nama lengkap wajib diisi.', $validator->errors()->first('full_name'));
        $this->assertSame('unit kerja wajib diisi.', $validator->errors()->first('institution_id'));
    }

    public function test_destructive_actions_use_reusable_bootstrap_confirmation_modal(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'status' => 'active',
        ]);
        Institution::create([
            'name' => 'Unit UI Audit',
            'level' => 'Unit',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('institutions.index', absolute: false))
            ->assertOk()
            ->assertSee('data-confirm-message=', escape: false)
            ->assertSee('id="confirm-action-modal"', escape: false)
            ->assertDontSee('return confirm(', escape: false);
    }
}
