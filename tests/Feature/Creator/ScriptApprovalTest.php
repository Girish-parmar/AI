<?php

namespace Tests\Feature\Creator;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Script;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_creator_can_create_submit_and_get_a_script_approved(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($creator)->post('/creator/scripts', [
            'title' => 'Backup Script',
            'description' => 'Automates nightly backups.',
            'category' => 'DevOps',
            'price' => 12.5,
        ])->assertRedirect(route('creator.scripts.index'));

        $script = Script::where('title', 'Backup Script')->firstOrFail();
        $this->assertSame(ContentStatus::Draft, $script->status);

        $this->actingAs($creator)->post("/creator/scripts/{$script->id}/submit")
            ->assertRedirect(route('creator.scripts.index'));
        $this->assertSame(ContentStatus::Pending, $script->fresh()->status);

        $this->actingAs($admin)->post(route('admin.scripts.approve', $script))->assertRedirect();
        $this->assertSame(ContentStatus::Approved, $script->fresh()->status);
    }

    public function test_a_script_can_be_rejected_with_notes(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $script = Script::factory()->create(['creator_id' => $creator->id, 'status' => ContentStatus::Pending]);
        $script->approvals()->create(['requested_by' => $creator->id, 'status' => ApprovalStatus::Pending]);

        $this->actingAs($admin)->post(route('admin.scripts.reject', $script), ['notes' => 'Not secure enough.']);

        $this->assertSame(ContentStatus::Rejected, $script->fresh()->status);
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Script::class,
            'approvable_id' => $script->id,
            'notes' => 'Not secure enough.',
        ]);
    }

    public function test_a_creator_cannot_edit_another_creators_script(): void
    {
        $owner = User::factory()->create(['role' => Role::Creator]);
        $other = User::factory()->create(['role' => Role::Creator]);
        $script = Script::factory()->create(['creator_id' => $owner->id]);

        $this->actingAs($other)->get("/creator/scripts/{$script->id}/edit")->assertForbidden();
    }
}
