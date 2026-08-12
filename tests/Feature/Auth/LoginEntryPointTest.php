<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginEntryPointTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guest_and_authenticated_users_by_role(): void
    {
        $this->get('/')
            ->assertRedirect(route('login', absolute: false));

        $destinations = [
            'super_admin' => route('dashboard', absolute: false),
            'hr_admin' => route('dashboard', absolute: false),
            'pegawai' => route('pegawai.dashboard', absolute: false),
            'panitia' => route('scanner.dashboard', absolute: false),
        ];

        foreach ($destinations as $role => $destination) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)
                ->get('/')
                ->assertRedirect($destination);

            auth()->logout();
        }
    }

    public function test_login_page_is_guest_only_and_contains_only_supported_auth_options(): void
    {
        $response = $this->get(route('login', absolute: false));

        $response
            ->assertOk()
            ->assertSee('Selamat Datang')
            ->assertSee('Sistem Informasi Kepegawaian')
            ->assertSee('name="_token"', escape: false)
            ->assertSee('Ingat saya')
            ->assertSee('Lupa password?')
            ->assertSee(route('password.request', absolute: false), escape: false)
            ->assertDontSee('Register')
            ->assertDontSee('Daftar')
            ->assertDontSee('Google')
            ->assertDontSee('Facebook')
            ->assertDontSee('Apple');

        foreach (['super_admin', 'hr_admin', 'pegawai', 'panitia'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);

            $this->actingAs($user)
                ->get(route('login', absolute: false))
                ->assertRedirect($this->destinationFor($role));

            auth()->logout();
        }
    }

    public function test_successful_login_redirects_each_role_to_its_server_side_destination(): void
    {
        foreach (['super_admin', 'hr_admin', 'pegawai', 'panitia'] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'status' => 'active',
                'password' => 'password',
            ]);

            $this->post(route('login', absolute: false), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect($this->destinationFor($role));

            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_login_keeps_only_intended_routes_authorized_for_the_authenticated_role(): void
    {
        $employee = User::factory()->create([
            'role' => 'pegawai',
            'status' => 'active',
            'password' => 'password',
        ]);

        $this->withSession(['url.intended' => route('dashboard', absolute: false)])
            ->post(route('login', absolute: false), [
                'email' => $employee->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('pegawai.dashboard', absolute: false));

        auth()->logout();

        $this->withSession(['url.intended' => route('pegawai.documents.index', absolute: false)])
            ->post(route('login', absolute: false), [
                'email' => $employee->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('pegawai.documents.index', absolute: false));
    }

    public function test_invalid_login_is_safe_and_remember_me_still_sets_recaller_cookie(): void
    {
        $user = User::factory()->create([
            'role' => 'pegawai',
            'status' => 'active',
            'password' => 'password',
        ]);

        $this->from(route('login', absolute: false))
            ->post(route('login', absolute: false), [
                'email' => $user->email,
                'password' => 'password-yang-salah',
            ])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringNotContainsString(
            'password-yang-salah',
            session('errors')->first('email')
        );

        $response = $this->post(route('login', absolute: false), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $response
            ->assertRedirect(route('pegawai.dashboard', absolute: false))
            ->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertAuthenticatedAs($user);
    }

    private function destinationFor(string $role): string
    {
        return match ($role) {
            'super_admin', 'hr_admin' => route('dashboard', absolute: false),
            'panitia' => route('scanner.dashboard', absolute: false),
            'pegawai' => route('pegawai.dashboard', absolute: false),
        };
    }
}
