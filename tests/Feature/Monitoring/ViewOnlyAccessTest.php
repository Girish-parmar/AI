<?php

namespace Tests\Feature\Monitoring;

use App\Enums\PayoutStatus;
use App\Enums\Role;
use App\Enums\TransactionStatus;
use App\Models\Course;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    private function purchaseTransaction(): Transaction
    {
        $buyer = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['price' => 20]);

        $this->actingAs($buyer)->post(route('user.courses.purchase', $course));

        return Transaction::where('user_id', $buyer->id)->firstOrFail();
    }

    public function test_monitoring_can_view_users_but_has_no_write_routes(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        User::factory()->count(2)->create();

        $this->actingAs($monitoring)->get(route('monitoring.users.index'))->assertStatus(200);
    }

    public function test_monitoring_cannot_manage_users_via_admin_routes(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $target = User::factory()->create(['role' => Role::User]);

        $this->actingAs($monitoring)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($monitoring)->get(route('admin.users.edit', $target))->assertForbidden();
        $this->actingAs($monitoring)->put(route('admin.users.update', $target), ['name' => 'x'])->assertForbidden();
        $this->actingAs($monitoring)->delete(route('admin.users.destroy', $target))->assertForbidden();
    }

    public function test_monitoring_can_view_transactions_but_cannot_succeed_or_fail_them(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $transaction = $this->purchaseTransaction();

        $this->actingAs($monitoring)->get(route('monitoring.transactions.index'))->assertStatus(200);

        $this->actingAs($monitoring)->post(route('accounts.transactions.succeed', $transaction))->assertForbidden();
        $this->actingAs($monitoring)->post(route('accounts.transactions.fail', $transaction))->assertForbidden();

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
    }

    public function test_monitoring_can_view_payouts_but_cannot_record_or_settle_them(): void
    {
        $monitoring = User::factory()->create(['role' => Role::Monitoring]);
        $creator = User::factory()->create(['role' => Role::Creator]);
        $payout = Payout::create(['creator_id' => $creator->id, 'amount' => 50, 'status' => PayoutStatus::Pending]);

        $this->actingAs($monitoring)->get(route('monitoring.payouts.index'))->assertStatus(200);

        $this->actingAs($monitoring)->post(route('accounts.payouts.store'), [
            'creator_id' => $creator->id,
            'amount' => 10,
        ])->assertForbidden();
        $this->actingAs($monitoring)->post(route('accounts.payouts.pay', $payout))->assertForbidden();
        $this->actingAs($monitoring)->post(route('accounts.payouts.fail', $payout))->assertForbidden();

        $this->assertSame(PayoutStatus::Pending, $payout->fresh()->status);
    }

    public function test_admin_can_view_transactions_but_cannot_succeed_or_fail_them(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $transaction = $this->purchaseTransaction();

        $this->actingAs($admin)->get(route('admin.transactions.index'))->assertStatus(200);

        $this->actingAs($admin)->post(route('accounts.transactions.succeed', $transaction))->assertForbidden();
        $this->actingAs($admin)->post(route('accounts.transactions.fail', $transaction))->assertForbidden();

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
    }

    public function test_admin_can_view_payouts_but_cannot_record_or_settle_them(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $creator = User::factory()->create(['role' => Role::Creator]);
        $payout = Payout::create(['creator_id' => $creator->id, 'amount' => 50, 'status' => PayoutStatus::Pending]);

        $this->actingAs($admin)->get(route('admin.payouts.index'))->assertStatus(200);

        $this->actingAs($admin)->post(route('accounts.payouts.store'), [
            'creator_id' => $creator->id,
            'amount' => 10,
        ])->assertForbidden();
        $this->actingAs($admin)->post(route('accounts.payouts.pay', $payout))->assertForbidden();
        $this->actingAs($admin)->post(route('accounts.payouts.fail', $payout))->assertForbidden();

        $this->assertSame(PayoutStatus::Pending, $payout->fresh()->status);
    }

    public function test_admin_can_view_the_shared_audit_log_page(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get(route('monitoring.audit-logs.index'))->assertStatus(200);
    }

    public function test_admin_cannot_manage_legal_documents(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->get(route('monitoring.legal-documents.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('monitoring.legal-documents.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.legal-documents.index'))->assertStatus(200);
    }

    public function test_creator_and_user_cannot_reach_any_of_the_new_view_only_routes(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $user = User::factory()->create(['role' => Role::User]);

        foreach ([$creator, $user] as $actor) {
            $this->actingAs($actor)->get(route('monitoring.users.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('monitoring.transactions.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('monitoring.payouts.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.transactions.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.payouts.index'))->assertForbidden();
        }
    }

    public function test_superadmin_retains_full_access_via_existing_shared_routes(): void
    {
        $superadmin = User::factory()->create(['role' => Role::SuperAdmin]);

        $this->actingAs($superadmin)->get(route('accounts.transactions.index'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('accounts.payouts.index'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('admin.users.index'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('monitoring.audit-logs.index'))->assertStatus(200);
        $this->actingAs($superadmin)->get(route('monitoring.legal-documents.index'))->assertStatus(200);
    }
}
