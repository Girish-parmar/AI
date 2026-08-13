<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Course;
use App\Models\DemoAccess;
use App\Models\Script;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_grant_demo_access_and_the_user_is_notified(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create();

        $response = $this->actingAs($admin)->post(route('admin.demo-access.store'), [
            'user_id' => $user->id,
            'resource' => "course:{$course->id}",
            'days' => 5,
        ]);

        $response->assertRedirect(route('admin.demo-access.index'));
        $this->assertDatabaseHas('demo_access', [
            'user_id' => $user->id,
            'resource_type' => Course::class,
            'resource_id' => $course->id,
            'granted_by' => $admin->id,
        ]);
        Notification::assertSentTo($user, \App\Notifications\DemoAccessGranted::class);
    }

    public function test_a_duplicate_active_grant_for_the_same_user_and_resource_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create();

        DemoAccess::create([
            'user_id' => $user->id,
            'resource_type' => Course::class,
            'resource_id' => $course->id,
            'granted_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.demo-access.store'), [
            'user_id' => $user->id,
            'resource' => "course:{$course->id}",
            'days' => 5,
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, DemoAccess::count());
    }

    public function test_demo_access_cannot_be_granted_for_unapproved_content(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->pending()->create();

        $this->actingAs($admin)->post(route('admin.demo-access.store'), [
            'user_id' => $user->id,
            'resource' => "course:{$course->id}",
            'days' => 5,
        ])->assertStatus(404);
    }

    public function test_demo_access_cannot_be_granted_to_a_non_user_role(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $creator = User::factory()->create(['role' => Role::Creator]);
        $course = Course::factory()->approved()->create();

        $this->actingAs($admin)->post(route('admin.demo-access.store'), [
            'user_id' => $creator->id,
            'resource' => "course:{$course->id}",
            'days' => 5,
        ])->assertStatus(404);
    }

    public function test_admin_can_revoke_an_active_grant(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $user = User::factory()->create(['role' => Role::User]);
        $grant = DemoAccess::create([
            'user_id' => $user->id,
            'resource_type' => Course::class,
            'resource_id' => Course::factory()->approved()->create()->id,
            'granted_by' => $admin->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin)->post(route('admin.demo-access.revoke', $grant));

        $this->assertTrue($grant->fresh()->expires_at->isPast());
    }

    public function test_an_already_expired_grant_cannot_be_revoked_again(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $grant = DemoAccess::create([
            'user_id' => User::factory()->create(['role' => Role::User])->id,
            'resource_type' => Script::class,
            'resource_id' => Script::factory()->approved()->create()->id,
            'granted_by' => $admin->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)->post(route('admin.demo-access.revoke', $grant))->assertStatus(422);
    }

    public function test_a_user_sees_their_active_demo_access_on_the_course_page(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create();
        DemoAccess::create([
            'user_id' => $user->id,
            'resource_type' => Course::class,
            'resource_id' => $course->id,
            'expires_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($user)->get(route('user.courses.show', $course));

        $response->assertStatus(200);
        $response->assertSee('demo access');
    }

    public function test_a_user_only_sees_their_own_demo_access_grants(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $otherUser = User::factory()->create(['role' => Role::User]);

        $mine = DemoAccess::create([
            'user_id' => $user->id,
            'resource_type' => Course::class,
            'resource_id' => Course::factory()->approved()->create(['title' => 'My Demo Course'])->id,
            'expires_at' => now()->addDays(3),
        ]);
        DemoAccess::create([
            'user_id' => $otherUser->id,
            'resource_type' => Course::class,
            'resource_id' => Course::factory()->approved()->create(['title' => 'Someone Elses Course'])->id,
            'expires_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($user)->get(route('user.demo-access.index'));

        $response->assertSee('My Demo Course');
        $response->assertDontSee('Someone Elses Course');
    }

    public function test_only_admin_and_superadmin_can_manage_demo_access(): void
    {
        foreach ([Role::Creator, Role::User, Role::Monitoring, Role::Accounts] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor)->get(route('admin.demo-access.index'))->assertForbidden();
        }
    }
}
