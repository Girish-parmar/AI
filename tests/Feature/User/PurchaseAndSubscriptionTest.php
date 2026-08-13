<?php

namespace Tests\Feature\User;

use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Course;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PurchaseStatusUpdated;
use App\Notifications\SubscriptionStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PurchaseAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_buy_an_approved_course(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['price' => 25]);

        $response = $this->actingAs($user)->post(route('user.courses.purchase', $course));

        $response->assertRedirect(route('user.purchases.index'));
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'purchasable_type' => Course::class,
            'purchasable_id' => $course->id,
            'status' => PurchaseStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => '25.00',
            'status' => TransactionStatus::Pending->value,
        ]);
    }

    public function test_a_user_cannot_buy_an_unapproved_course(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->pending()->create();

        $this->actingAs($user)->post(route('user.courses.purchase', $course))->assertStatus(404);
    }

    public function test_a_user_cannot_buy_the_same_course_twice_while_a_purchase_is_open(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create();

        $this->actingAs($user)->post(route('user.courses.purchase', $course));
        $response = $this->actingAs($user)->post(route('user.courses.purchase', $course));

        $response->assertStatus(422);
        $this->assertSame(1, Purchase::where('user_id', $user->id)->count());
    }

    public function test_accounts_marking_a_transaction_succeeded_completes_the_purchase_and_notifies_the_buyer(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $course = Course::factory()->approved()->create(['price' => 40]);

        $this->actingAs($user)->post(route('user.courses.purchase', $course));
        $purchase = Purchase::where('user_id', $user->id)->firstOrFail();
        $transaction = $purchase->transactions()->firstOrFail();

        $this->actingAs($accounts)->post(route('accounts.transactions.succeed', $transaction));

        $this->assertSame(PurchaseStatus::Completed, $purchase->fresh()->status);
        $this->assertSame(TransactionStatus::Succeeded, $transaction->fresh()->status);
        Notification::assertSentTo($user, PurchaseStatusUpdated::class);
    }

    public function test_accounts_marking_a_transaction_failed_fails_the_purchase(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $course = Course::factory()->approved()->create();

        $this->actingAs($user)->post(route('user.courses.purchase', $course));
        $purchase = Purchase::where('user_id', $user->id)->firstOrFail();
        $transaction = $purchase->transactions()->firstOrFail();

        $this->actingAs($accounts)->post(route('accounts.transactions.fail', $transaction));

        $this->assertSame(PurchaseStatus::Failed, $purchase->fresh()->status);
    }

    public function test_a_transaction_cannot_be_resolved_twice(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $course = Course::factory()->approved()->create();

        $this->actingAs($user)->post(route('user.courses.purchase', $course));
        $transaction = Transaction::first();

        $this->actingAs($accounts)->post(route('accounts.transactions.succeed', $transaction));
        $this->actingAs($accounts)->post(route('accounts.transactions.succeed', $transaction))->assertStatus(422);
    }

    public function test_a_user_can_subscribe_to_an_active_plan(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan))
            ->assertRedirect(route('user.subscription.show'));

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending->value,
        ]);
    }

    public function test_a_user_cannot_subscribe_to_an_inactive_plan(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $plan = SubscriptionPlan::factory()->inactive()->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan))->assertStatus(404);
    }

    public function test_a_user_cannot_hold_two_open_subscriptions(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $planA = SubscriptionPlan::factory()->create();
        $planB = SubscriptionPlan::factory()->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $planA));
        $this->actingAs($user)->post(route('user.plans.subscribe', $planB))->assertStatus(422);
    }

    public function test_accounts_activating_a_subscription_notifies_the_subscriber(): void
    {
        Notification::fake();

        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan));
        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
        $transaction = $subscription->transactions()->firstOrFail();

        $this->actingAs($accounts)->post(route('accounts.transactions.succeed', $transaction));

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        Notification::assertSentTo($user, SubscriptionStatusUpdated::class);
    }

    public function test_a_user_can_cancel_their_own_active_subscription(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::factory()->create()->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subDays(5),
        ]);

        $this->actingAs($user)->post(route('user.subscription.cancel'))
            ->assertRedirect(route('user.subscription.show'));

        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->fresh()->status);
        $this->assertNotNull($subscription->fresh()->cancelled_at);
    }

    public function test_cancelling_with_no_open_subscription_returns_not_found(): void
    {
        $user = User::factory()->create(['role' => Role::User]);

        $this->actingAs($user)->post(route('user.subscription.cancel'))->assertStatus(404);
    }
}
