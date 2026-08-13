<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\LegalDocument;
use App\Models\User;
use App\Notifications\DatabaseSizeWarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HostingLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_past_the_retention_window_are_pruned(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);

        $old = AuditLog::create(['user_id' => $user->id, 'action' => 'old.action']);
        $old->forceFill(['created_at' => now()->subDays(400)])->save();

        $recent = AuditLog::create(['user_id' => $user->id, 'action' => 'recent.action']);

        $this->artisan('model:prune')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
    }

    public function test_read_notifications_are_pruned_after_retention_and_unread_after_double(): void
    {
        $user = User::factory()->create(['role' => Role::User]);

        $insert = function (?string $readAt, int $ageDays) use ($user): string {
            $id = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'id' => $id,
                'type' => 'App\\Notifications\\Test',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => '{}',
                'read_at' => $readAt,
                'created_at' => now()->subDays($ageDays),
                'updated_at' => now()->subDays($ageDays),
            ]);

            return $id;
        };

        $oldRead = $insert(now()->subDays(100)->toDateTimeString(), 100);
        $recentRead = $insert(now()->subDay()->toDateTimeString(), 30);
        $ancientUnread = $insert(null, 200);
        $oldUnread = $insert(null, 100);

        $this->artisan('notifications:prune')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $oldRead]);
        $this->assertDatabaseHas('notifications', ['id' => $recentRead]);
        $this->assertDatabaseMissing('notifications', ['id' => $ancientUnread]);
        $this->assertDatabaseHas('notifications', ['id' => $oldUnread]);
    }

    public function test_hosting_health_notifies_superadmins_when_the_database_crosses_the_warn_threshold(): void
    {
        Notification::fake();

        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);
        User::factory()->create(['role' => Role::Admin]);

        // Any real database is larger than a 1-byte cap, so this forces
        // the critical threshold without needing to bloat the test DB.
        config(['hosting.database_cap_bytes' => 1]);

        $this->artisan('hosting:health')->assertSuccessful();

        Notification::assertSentTo($superadmin, DatabaseSizeWarning::class);
        Notification::assertCount(1);
    }

    public function test_hosting_health_stays_quiet_under_the_warn_threshold(): void
    {
        Notification::fake();

        User::factory()->create(['role' => Role::SuperAdmin]);

        $this->artisan('hosting:health')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_the_footer_link_cache_is_invalidated_when_a_document_changes(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);

        $document = LegalDocument::factory()->create([
            'type' => 'terms_of_service',
            'title' => 'Terms of Service',
            'created_by' => $monitoring->id,
        ]);

        // Prime the cache while nothing is published.
        $this->assertSame([], LegalDocument::footerLinks());
        $this->assertTrue(cache()->has(LegalDocument::FOOTER_CACHE_KEY));

        // Publishing must bust the cached empty list, not serve it stale.
        $document->update(['published_at' => now()]);
        $this->assertSame('Terms of Service', LegalDocument::footerLinks()[0]['title']);

        $document->update(['published_at' => null]);
        $this->assertSame([], LegalDocument::footerLinks());
    }
}
