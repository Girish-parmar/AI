<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register_and_are_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jamie Rivera',
            'email' => 'jamie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'jamie@example.com',
            'name' => 'Jamie Rivera',
        ]);
    }

    public function test_self_registration_always_creates_a_plain_user_account(): void
    {
        // The request has no `role` field in its form, but an attacker
        // could still try to smuggle one through — the controller must
        // hardcode the role server-side regardless of what's posted.
        $this->post('/register', [
            'name' => 'Attempted Admin',
            'email' => 'attempted-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ]);

        $user = User::where('email', 'attempted-admin@example.com')->firstOrFail();

        $this->assertSame(Role::User, $user->role);
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jamie Rivera',
            'email' => 'jamie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jamie@example.com']);
    }

    public function test_registration_requires_a_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'name' => 'Jamie Rivera',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_an_authenticated_user_is_redirected_away_from_the_register_page(): void
    {
        $user = User::factory()->create(['role' => Role::User]);

        $this->actingAs($user)->get('/register')->assertRedirect(route('dashboard'));
    }
}
