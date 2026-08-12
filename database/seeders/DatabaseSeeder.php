<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Course;
use App\Models\Script;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates one demo account per role for local development so every
     * dashboard can be exercised without registering manually (password
     * for all demo accounts is "password"), plus a spread of courses and
     * scripts across every status so approval/browse flows have data to
     * show. Local/dev use only.
     */
    public function run(): void
    {
        $users = collect(Role::cases())->mapWithKeys(function (Role $role) {
            $user = User::factory()->create([
                'name' => $role->label().' Demo',
                'email' => "{$role->value}@example.com",
                'role' => $role,
            ]);

            return [$role->value => $user];
        });

        $creator = $users['creator'];

        Course::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Intro to Guitar',
            'status' => ContentStatus::Draft,
        ]);

        $this->submitted(
            Course::factory()->pending()->create(['creator_id' => $creator->id, 'title' => 'Advanced Photography']),
            $creator
        );

        Course::factory()->approved()->create(['creator_id' => $creator->id, 'title' => 'Laravel for Beginners']);

        $this->reviewed(
            Course::factory()->rejected()->create(['creator_id' => $creator->id, 'title' => 'Get Rich Quick']),
            $creator,
            $users['admin'],
            ApprovalStatus::Rejected,
            'Content does not meet our quality guidelines.'
        );

        Script::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'CSV Cleanup Utility',
            'status' => ContentStatus::Draft,
        ]);

        $this->submitted(
            Script::factory()->pending()->create(['creator_id' => $creator->id, 'title' => 'Auto-Deploy Pipeline']),
            $creator
        );

        Script::factory()->approved()->create(['creator_id' => $creator->id, 'title' => 'Log Parser']);
    }

    private function submitted(Course|Script $content, User $creator): void
    {
        $content->approvals()->create([
            'requested_by' => $creator->id,
            'status' => ApprovalStatus::Pending,
        ]);
    }

    private function reviewed(Course|Script $content, User $creator, User $reviewer, ApprovalStatus $status, ?string $notes): void
    {
        $content->approvals()->create([
            'requested_by' => $creator->id,
            'reviewed_by' => $reviewer->id,
            'status' => $status,
            'notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }
}
