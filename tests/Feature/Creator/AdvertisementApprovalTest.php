<?php

namespace Tests\Feature\Creator;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_creator_can_create_submit_and_get_an_advertisement_approved(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($creator)->post('/creator/advertisements', [
            'title' => 'Big Summer Sale',
            'target_url' => 'https://example.com/sale',
        ])->assertRedirect(route('creator.advertisements.index'));

        $ad = Advertisement::where('title', 'Big Summer Sale')->firstOrFail();
        $this->assertSame($creator->id, $ad->created_by);
        $this->assertSame(ContentStatus::Draft, $ad->status);

        $this->actingAs($creator)->post("/creator/advertisements/{$ad->id}/submit");
        $this->assertSame(ContentStatus::Pending, $ad->fresh()->status);

        $this->actingAs($admin)->post(route('admin.advertisements.approve', $ad));
        $this->assertSame(ContentStatus::Approved, $ad->fresh()->status);
    }

    public function test_target_url_rejects_a_javascript_uri(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);

        $response = $this->actingAs($creator)->post('/creator/advertisements', [
            'title' => 'Malicious',
            'target_url' => 'javascript:alert(1)',
        ]);

        $response->assertSessionHasErrors('target_url');
        $this->assertDatabaseMissing('advertisements', ['title' => 'Malicious']);
    }

    public function test_only_approved_advertisements_are_live_on_the_user_dashboard(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);

        $live = Advertisement::factory()->approved()->create(['created_by' => $creator->id]);
        Advertisement::factory()->pending()->create(['created_by' => $creator->id]);
        Advertisement::factory()->create(['created_by' => $creator->id]); // draft
        $expired = Advertisement::factory()->create([
            'created_by' => $creator->id,
            'status' => ContentStatus::Approved,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        $liveIds = Advertisement::live()->pluck('id');

        $this->assertTrue($liveIds->contains($live->id));
        $this->assertFalse($liveIds->contains($expired->id));
        $this->assertCount(1, $liveIds);
    }

    public function test_a_creator_cannot_delete_another_creators_advertisement(): void
    {
        $owner = User::factory()->create(['role' => Role::Creator]);
        $other = User::factory()->create(['role' => Role::Creator]);
        $ad = Advertisement::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($other)->delete("/creator/advertisements/{$ad->id}")->assertForbidden();
    }

    public function test_monitoring_can_view_but_not_approve_advertisements(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $ad = Advertisement::factory()->create(['created_by' => $creator->id, 'status' => ContentStatus::Pending]);
        $ad->approvals()->create(['requested_by' => $creator->id, 'status' => ApprovalStatus::Pending]);
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);

        $this->actingAs($monitoring)->get(route('monitoring.advertisements.index'))->assertStatus(200);
        $this->actingAs($monitoring)->post(route('admin.advertisements.approve', $ad))->assertForbidden();
    }
}
