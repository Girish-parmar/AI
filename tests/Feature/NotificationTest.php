<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Models\User;
use App\Notifications\ContentReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_mark_their_own_notification_read_and_is_redirected_to_its_url(): void
    {
        $user = User::factory()->create();
        $user->notify(new ContentReviewed('course', 'My Course', 1, ApprovalStatus::Approved, null));
        $notification = $user->notifications()->firstOrFail();

        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

        $this->assertNotNull($notification->fresh()->read_at);
        $response->assertRedirect($notification->data['url']);
    }

    public function test_a_user_cannot_mark_another_users_notification_read(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $owner->notify(new ContentReviewed('course', 'My Course', 1, ApprovalStatus::Approved, null));
        $notification = $owner->notifications()->firstOrFail();

        $response = $this->actingAs($intruder)->post(route('notifications.read', $notification->id));

        $response->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_only_touches_the_current_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->notify(new ContentReviewed('course', 'Mine', 1, ApprovalStatus::Approved, null));
        $otherUser->notify(new ContentReviewed('course', 'Theirs', 2, ApprovalStatus::Approved, null));

        $this->actingAs($user)->post(route('notifications.read-all'));

        $this->assertNotNull($user->notifications()->first()->fresh()->read_at);
        $this->assertNull($otherUser->notifications()->first()->fresh()->read_at);
    }

    public function test_the_notifications_index_lists_only_the_authenticated_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->notify(new ContentReviewed('course', 'My Notification Course', 1, ApprovalStatus::Approved, null));
        $otherUser->notify(new ContentReviewed('course', 'Someone Elses Course', 2, ApprovalStatus::Approved, null));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertSee('My Notification Course');
        $response->assertDontSee('Someone Elses Course');
    }

    public function test_guests_cannot_reach_notification_routes(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
    }
}
