<?php

namespace Tests\Feature\Accounts;

use App\Enums\PayoutStatus;
use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Models\Course;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    private function creatorWithEarnings(float $amount): User
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $buyer = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['creator_id' => $creator->id, 'price' => $amount]);

        Purchase::create([
            'user_id' => $buyer->id,
            'purchasable_type' => Course::class,
            'purchasable_id' => $course->id,
            'price' => $amount,
            'status' => PurchaseStatus::Completed,
        ]);

        return $creator;
    }

    public function test_accounts_can_record_a_payout_up_to_the_outstanding_balance(): void
    {
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = $this->creatorWithEarnings(100);

        $response = $this->actingAs($accounts)->post(route('accounts.payouts.store'), [
            'creator_id' => $creator->id,
            'amount' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payouts', [
            'creator_id' => $creator->id,
            'amount' => '100.00',
            'status' => PayoutStatus::Pending->value,
        ]);
    }

    public function test_a_payout_cannot_exceed_the_creators_outstanding_balance(): void
    {
        // Regression test for the overpayment bug found during manual
        // testing: the amount field only had a client-side max attribute,
        // with no server-side check against the actual outstanding balance.
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = $this->creatorWithEarnings(50);

        $response = $this->actingAs($accounts)->post(route('accounts.payouts.store'), [
            'creator_id' => $creator->id,
            'amount' => 500,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('payouts', ['creator_id' => $creator->id]);
    }

    public function test_a_payout_cannot_exceed_the_remaining_balance_after_a_prior_payout(): void
    {
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = $this->creatorWithEarnings(100);

        Payout::create(['creator_id' => $creator->id, 'amount' => 80, 'status' => PayoutStatus::Paid, 'paid_at' => now()]);

        $response = $this->actingAs($accounts)->post(route('accounts.payouts.store'), [
            'creator_id' => $creator->id,
            'amount' => 30,
        ]);

        $response->assertStatus(422);
    }

    public function test_accounts_can_mark_a_pending_payout_paid_and_the_creator_is_notified(): void
    {
        Notification::fake();

        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = $this->creatorWithEarnings(100);
        $payout = Payout::create(['creator_id' => $creator->id, 'amount' => 50, 'status' => PayoutStatus::Pending]);

        $this->actingAs($accounts)->post(route('accounts.payouts.pay', $payout));

        $this->assertSame(PayoutStatus::Paid, $payout->fresh()->status);
        $this->assertNotNull($payout->fresh()->paid_at);
        Notification::assertSentTo($creator, \App\Notifications\PayoutStatusUpdated::class);
    }

    public function test_accounts_can_mark_a_pending_payout_failed(): void
    {
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = $this->creatorWithEarnings(100);
        $payout = Payout::create(['creator_id' => $creator->id, 'amount' => 50, 'status' => PayoutStatus::Pending]);

        $this->actingAs($accounts)->post(route('accounts.payouts.fail', $payout));

        $this->assertSame(PayoutStatus::Failed, $payout->fresh()->status);
    }

    public function test_a_payout_cannot_be_marked_paid_twice(): void
    {
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = $this->creatorWithEarnings(100);
        $payout = Payout::create(['creator_id' => $creator->id, 'amount' => 50, 'status' => PayoutStatus::Paid, 'paid_at' => now()]);

        $this->actingAs($accounts)->post(route('accounts.payouts.pay', $payout))->assertStatus(422);
    }

    public function test_creator_and_admin_cannot_access_the_payouts_area(): void
    {
        $creator = User::factory()->create(['role' => Role::Creator]);
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($creator)->get(route('accounts.payouts.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('accounts.payouts.index'))->assertForbidden();
    }
}
