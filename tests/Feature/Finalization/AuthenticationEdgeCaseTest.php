<?php

namespace Tests\Feature\Finalization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_and_oversized_credentials_fail_without_a_server_error(): void
    {
        foreach ([
            [],
            ['email' => 'user@example.test'],
            ['password' => 'password'],
        ] as $credentials) {
            $this->from(route('login', absolute: false))
                ->post(route('login', absolute: false), $credentials)
                ->assertRedirect(route('login', absolute: false))
                ->assertSessionHasErrors();

            $this->assertGuest();
        }

        $this->from(route('login', absolute: false))
            ->post(route('login', absolute: false), [
                'email' => str_repeat('a', 500).'@example.test',
                'password' => str_repeat('p', 10_000),
            ])
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_rate_limit_blocks_repeated_failures(): void
    {
        $email = 'rate-limit@yapista.test';
        $key = $this->throttleKey($email);
        RateLimiter::clear($key);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login', absolute: false), [
                'email' => $email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));

        $this->post(route('login', absolute: false), [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        RateLimiter::clear($key);
    }

    public function test_successful_login_clears_previous_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'clear-limit@yapista.test',
            'password' => 'password',
            'role' => 'pegawai',
            'status' => 'active',
        ]);
        $key = $this->throttleKey($user->email);
        RateLimiter::clear($key);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post(route('login', absolute: false), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login', absolute: false), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('pegawai.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, RateLimiter::attempts($key));
    }

    public function test_logout_invalidates_access_to_protected_pages(): void
    {
        $user = User::factory()->create([
            'role' => 'pegawai',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('logout', absolute: false))
            ->assertRedirect('/');

        $this->assertGuest();
        $this->get(route('pegawai.dashboard', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    private function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|127.0.0.1');
    }
}
