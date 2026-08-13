<?php

namespace Tests\Feature\User;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionBillingCycleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_subscribing_to_a_plan_with_a_trial_grants_immediate_access_with_no_charge(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(now()->timestamp));

        $user = User::factory()->create(['role' => Role::User]);
        $plan = SubscriptionPlan::factory()->withTrial(14)->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan))
            ->assertRedirect(route('user.subscription.show'));

        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertTrue($subscription->onTrial());
        $this->assertEquals(now()->addDays(14), $subscription->trial_ends_at);
        $this->assertEquals(now()->addDays(14), $subscription->current_period_ends_at);
        $this->assertSame(0, $subscription->transactions()->count());
    }

    public function test_subscribing_to_a_plan_without_a_trial_is_unchanged(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan));

        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(SubscriptionStatus::Pending, $subscription->status);
        $this->assertFalse($subscription->onTrial());
        $this->assertNull($subscription->current_period_ends_at);
        $this->assertSame(1, $subscription->transactions()->count());
    }

    public function test_initial_activation_sets_the_current_period_end(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(now()->timestamp));

        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $plan = SubscriptionPlan::factory()->create(['billing_interval' => 'monthly']);

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan));
        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
        $transaction = $subscription->transactions()->firstOrFail();

        $this->actingAs($accounts)->post(route('accounts.transactions.succeed', $transaction));

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertEquals(now()->addDays(30), $subscription->fresh()->current_period_ends_at);
    }

    public function test_switching_plans_charges_the_prorated_difference_for_an_upgrade(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(now()->timestamp));

        $user = User::factory()->create(['role' => Role::User]);
        $oldPlan = SubscriptionPlan::factory()->create(['price' => 30, 'billing_interval' => 'monthly']);
        $newPlan = SubscriptionPlan::factory()->create(['price' => 60, 'billing_interval' => 'monthly']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $oldPlan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $response = $this->actingAs($user)->post(route('user.subscription.switch', $newPlan));
        $response->assertRedirect(route('user.subscription.show'));

        $subscription->refresh();
        $this->assertSame($newPlan->id, $subscription->subscription_plan_id);
        // credit = 30 * 15/30 = 15.00, charge = 60 * 15/30 = 30.00, due = 15.00
        $this->assertDatabaseHas('transactions', [
            'payable_type' => Subscription::class,
            'payable_id' => $subscription->id,
            'amount' => '15.00',
            'status' => TransactionStatus::Pending->value,
        ]);
    }

    public function test_switching_plans_charges_nothing_when_credit_covers_a_downgrade(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(now()->timestamp));

        $user = User::factory()->create(['role' => Role::User]);
        $oldPlan = SubscriptionPlan::factory()->create(['price' => 30, 'billing_interval' => 'monthly']);
        $newPlan = SubscriptionPlan::factory()->create(['price' => 10, 'billing_interval' => 'monthly']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $oldPlan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $this->actingAs($user)->post(route('user.subscription.switch', $newPlan))
            ->assertRedirect(route('user.subscription.show'));

        $subscription->refresh();
        $this->assertSame($newPlan->id, $subscription->subscription_plan_id);
        $this->assertSame(0, Transaction::where('payable_id', $subscription->id)->where('payable_type', Subscription::class)->count());
    }

    public function test_a_prorated_charge_succeeding_does_not_move_the_current_period_end(): void
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(now()->timestamp));

        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $oldPlan = SubscriptionPlan::factory()->create(['price' => 30, 'billing_interval' => 'monthly']);
        $newPlan = SubscriptionPlan::factory()->create(['price' => 60, 'billing_interval' => 'monthly']);
        $periodEnd = now()->addDays(15);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $oldPlan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'current_period_ends_at' => $periodEnd,
        ]);

        $this->actingAs($user)->post(route('user.subscription.switch', $newPlan));
        $transaction = $subscription->transactions()->firstOrFail();

        $this->actingAs($accounts)->post(route('accounts.transactions.succeed', $transaction));

        $this->assertEquals($periodEnd, $subscription->fresh()->current_period_ends_at);
    }

    public function test_a_failed_prorated_charge_does_not_cancel_the_subscription(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $oldPlan = SubscriptionPlan::factory()->create(['price' => 30, 'billing_interval' => 'monthly']);
        $newPlan = SubscriptionPlan::factory()->create(['price' => 60, 'billing_interval' => 'monthly']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $oldPlan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $this->actingAs($user)->post(route('user.subscription.switch', $newPlan));
        $transaction = $subscription->transactions()->firstOrFail();

        $this->actingAs($accounts)->post(route('accounts.transactions.fail', $transaction));

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
        $this->assertSame($newPlan->id, $subscription->fresh()->subscription_plan_id);
    }

    public function test_an_unpaid_initial_transaction_failing_still_cancels_the_subscription(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan));
        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
        $transaction = $subscription->transactions()->firstOrFail();

        $this->actingAs($accounts)->post(route('accounts.transactions.fail', $transaction));

        $this->assertSame(SubscriptionStatus::Cancelled, $subscription->fresh()->status);
    }

    public function test_a_user_cannot_switch_plans_while_on_trial(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $trialPlan = SubscriptionPlan::factory()->withTrial(14)->create();
        $otherPlan = SubscriptionPlan::factory()->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $trialPlan));

        $this->actingAs($user)->post(route('user.subscription.switch', $otherPlan))->assertStatus(422);
    }

    public function test_a_user_cannot_switch_to_the_same_plan_or_an_inactive_plan(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $plan = SubscriptionPlan::factory()->create();
        $inactivePlan = SubscriptionPlan::factory()->inactive()->create();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $this->actingAs($user)->post(route('user.subscription.switch', $plan))->assertStatus(422);
        $this->actingAs($user)->post(route('user.subscription.switch', $inactivePlan))->assertStatus(404);
    }

    public function test_a_user_cannot_switch_plans_with_a_pending_payment_outstanding(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $oldPlan = SubscriptionPlan::factory()->create();
        $newPlan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $oldPlan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'current_period_ends_at' => now()->addDays(15),
        ]);

        $subscription->transactions()->create([
            'user_id' => $user->id,
            'amount' => 5,
            'gateway' => 'manual',
            'status' => TransactionStatus::Pending,
        ]);

        $this->actingAs($user)->post(route('user.subscription.switch', $newPlan))->assertStatus(422);
    }

    public function test_admin_can_set_a_trial_period_on_a_plan(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->post(route('admin.subscription-plans.store'), [
            'name' => 'Trial Plan',
            'slug' => 'trial-plan',
            'price' => 19.99,
            'billing_interval' => 'monthly',
            'trial_days' => 30,
            'is_active' => '1',
        ]);

        $plan = SubscriptionPlan::where('slug', 'trial-plan')->firstOrFail();
        $this->assertTrue($plan->hasTrial());
        $this->assertSame(30, $plan->trial_days);
    }
}
