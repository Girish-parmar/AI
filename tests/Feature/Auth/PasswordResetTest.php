<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_reset_link_can_be_requested_for_a_registered_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_requesting_a_reset_link_for_an_unregistered_email_gives_the_same_response(): void
    {
        // No user enumeration: the response must be identical whether or
        // not the email actually has an account.
        Notification::fake();

        $response = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertSessionHas('status');
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-strong-password',
                'password_confirmation' => 'new-strong-password',
            ]);

            $response->assertRedirect(route('login'));
            $this->assertTrue(Hash::check('new-strong-password', $user->fresh()->password));

            return true;
        });
    }

    public function test_password_reset_token_cannot_be_reused(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'first-new-password',
                'password_confirmation' => 'first-new-password',
            ]);

            // Second attempt with the same token must fail.
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'second-new-password',
                'password_confirmation' => 'second-new-password',
            ]);

            $response->assertSessionHasErrors('email');
            $this->assertTrue(Hash::check('first-new-password', $user->fresh()->password));

            return true;
        });
    }

    public function test_password_reset_rejects_a_bogus_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
