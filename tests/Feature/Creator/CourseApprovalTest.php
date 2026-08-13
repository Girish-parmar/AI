<?php

namespace Tests\Feature\Creator;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Course;
use App\Models\User;
use App\Notifications\ContentReviewed;
use App\Notifications\ContentSubmittedForReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CourseApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_creator_can_create_a_draft_course(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);

        $response = $this->actingAs($creator)->post('/creator/courses', [
            'title' => 'Intro to Testing',
            'description' => 'Learn the basics.',
            'category' => 'Programming',
            'price' => 19.99,
        ]);

        $response->assertRedirect(route('creator.courses.index'));
        $this->assertDatabaseHas('courses', [
            'title' => 'Intro to Testing',
            'creator_id' => $creator->id,
            'status' => ContentStatus::Draft->value,
        ]);
    }

    public function test_submitting_a_draft_course_creates_a_pending_approval_and_notifies_admins(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);
        $course = Course::factory()->create(['creator_id' => $creator->id, 'status' => ContentStatus::Draft]);

        $response = $this->actingAs($creator)->post("/creator/courses/{$course->id}/submit");

        $response->assertRedirect(route('creator.courses.index'));
        $this->assertSame(ContentStatus::Pending, $course->fresh()->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Course::class,
            'approvable_id' => $course->id,
            'requested_by' => $creator->id,
            'status' => ApprovalStatus::Pending->value,
        ]);

        Notification::assertSentTo([$admin, $superadmin], ContentSubmittedForReview::class);
    }

    public function test_a_pending_or_approved_course_cannot_be_resubmitted(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $pending = Course::factory()->create(['creator_id' => $creator->id, 'status' => ContentStatus::Pending]);
        $approved = Course::factory()->create(['creator_id' => $creator->id, 'status' => ContentStatus::Approved]);

        $this->actingAs($creator)->post("/creator/courses/{$pending->id}/submit")->assertStatus(422);
        $this->actingAs($creator)->post("/creator/courses/{$approved->id}/submit")->assertStatus(422);
    }

    public function test_a_creator_cannot_edit_or_delete_another_creators_course(): void
    {
        $owner = User::factory()->create(['role' => Role::Creator]);
        $otherCreator = User::factory()->create(['role' => Role::Creator]);
        $course = Course::factory()->create(['creator_id' => $owner->id]);

        $this->actingAs($otherCreator)->get("/creator/courses/{$course->id}/edit")->assertForbidden();
        $this->actingAs($otherCreator)->delete("/creator/courses/{$course->id}")->assertForbidden();
    }

    public function test_admin_can_edit_or_delete_any_creators_course(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $course = Course::factory()->create(['creator_id' => $creator->id]);

        // Admin's edit/delete lives under the shared /admin/courses/* routes.
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($admin)->get(route('admin.courses.edit', $course))->assertStatus(200);
    }

    public function test_admin_can_approve_a_pending_course_and_the_creator_is_notified(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $course = Course::factory()->create(['creator_id' => $creator->id, 'status' => ContentStatus::Pending]);
        $approval = $course->approvals()->create(['requested_by' => $creator->id, 'status' => ApprovalStatus::Pending]);

        $response = $this->actingAs($admin)->post(route('admin.courses.approve', $course));

        $response->assertRedirect();
        $this->assertSame(ContentStatus::Approved, $course->fresh()->status);
        $this->assertSame(ApprovalStatus::Approved, $approval->fresh()->status);
        $this->assertSame($admin->id, $approval->fresh()->reviewed_by);
        Notification::assertSentTo($creator, ContentReviewed::class);
    }

    public function test_admin_can_reject_a_pending_course_with_notes(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $course = Course::factory()->create(['creator_id' => $creator->id, 'status' => ContentStatus::Pending]);
        $approval = $course->approvals()->create(['requested_by' => $creator->id, 'status' => ApprovalStatus::Pending]);

        $this->actingAs($admin)->post(route('admin.courses.reject', $course), [
            'notes' => 'Needs more detail.',
        ]);

        $this->assertSame(ContentStatus::Rejected, $course->fresh()->status);
        $this->assertSame(ApprovalStatus::Rejected, $approval->fresh()->status);
        $this->assertSame('Needs more detail.', $approval->fresh()->notes);

        Notification::assertSentTo($creator, ContentReviewed::class, function ($notification) use ($creator) {
            return str_contains($notification->toArray($creator)['message'], 'rejected');
        });
    }

    public function test_a_non_pending_course_cannot_be_approved_or_rejected_twice(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $course = Course::factory()->create(['creator_id' => $creator->id, 'status' => ContentStatus::Approved]);

        $this->actingAs($admin)->post(route('admin.courses.approve', $course))->assertStatus(422);
        $this->actingAs($admin)->post(route('admin.courses.reject', $course))->assertStatus(422);
    }

    public function test_only_admin_and_superadmin_can_approve_courses(): void
    {
        $course = Course::factory()->create(['status' => ContentStatus::Pending]);
        $course->approvals()->create(['requested_by' => $course->creator_id, 'status' => ApprovalStatus::Pending]);

        foreach ([Role::Creator, Role::User, Role::Monitoring, Role::Accounts] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor)->post(route('admin.courses.approve', $course))->assertForbidden();
        }
    }

    public function test_monitoring_can_view_but_not_approve_courses(): void
    {
        Course::factory()->approved()->create();
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);

        $this->actingAs($monitoring)->get(route('monitoring.courses.index'))->assertStatus(200);
    }
}
