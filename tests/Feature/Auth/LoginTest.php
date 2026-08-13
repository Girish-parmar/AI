<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_users_can_authenticate_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logging_in_redirects_to_the_role_specific_dashboard(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator, 'password' => Hash::make('password')]);

        $this->post('/login', ['email' => $creator->email, 'password' => 'password']);

        $this->get('/dashboard')->assertRedirect(route('creator.dashboard'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create(['email' => 'ratelimit@example.com', 'password' => Hash::make('correct-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_throttle_is_keyed_by_email_so_one_account_cannot_lock_out_another(): void
    {
        RateLimiter::clear('login:'.hash('sha256', 'victim@example.com|127.0.0.1'));

        $victim = User::factory()->create(['email' => 'victim@example.com', 'password' => Hash::make('correct-password')]);
        User::factory()->create(['email' => 'attacker@example.com', 'password' => Hash::make('correct-password')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'attacker@example.com', 'password' => 'wrong-password']);
        }

        $response = $this->post('/login', ['email' => $victim->email, 'password' => 'correct-password']);

        $this->assertAuthenticatedAs($victim);
        $response->assertRedirect(route('dashboard'));
    }
}
