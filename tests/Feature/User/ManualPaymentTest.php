<?php

namespace Tests\Feature\User;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Course;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function configureBankDetails(): void
    {
        config([
            'payments.manual.account_name' => 'Awaiken Ltd',
            'payments.manual.bank_name' => 'Example Bank',
            'payments.manual.account_number' => '1234567890',
            'payments.manual.upi_id' => 'awaiken@examplebank',
            'payments.manual.notes' => 'Confirmed within one business day.',
        ]);
    }

    public function test_a_buyer_with_an_unpaid_purchase_is_told_how_to_pay_and_what_to_quote(): void
    {
        $this->configureBankDetails();

        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['price' => 25]);

        $this->actingAs($user)->post(route('user.courses.purchase', $course));
        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->get(route('user.purchases.index'))
            ->assertStatus(200)
            ->assertSee('How to pay')
            ->assertSee('Example Bank')
            ->assertSee('1234567890')
            ->assertSee('awaiken@examplebank')
            ->assertSee('Confirmed within one business day.')
            ->assertSee($transaction->reference());
    }

    public function test_the_how_to_pay_panel_is_hidden_once_nothing_is_outstanding(): void
    {
        $this->configureBankDetails();

        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $course = Course::factory()->approved()->create(['price' => 25]);

        $this->actingAs($user)->post(route('user.courses.purchase', $course));
        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($accounts)->post(route('accounts.transactions.succeed', $transaction));

        $this->actingAs($user)->get(route('user.purchases.index'))
            ->assertStatus(200)
            ->assertDontSee('How to pay');
    }

    public function test_unconfigured_bank_details_degrade_to_an_honest_message_not_an_empty_panel(): void
    {
        // Every field blank, as on a fresh deploy before .env is filled in.
        config(['payments.manual' => array_map(fn () => null, config('payments.manual'))]);

        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['price' => 25]);

        $this->actingAs($user)->post(route('user.courses.purchase', $course));

        $this->actingAs($user)->get(route('user.purchases.index'))
            ->assertStatus(200)
            ->assertSee('Payment details are being finalised');
    }

    public function test_a_subscriber_awaiting_payment_sees_the_reference_for_that_charge(): void
    {
        $this->configureBankDetails();

        $user = User::factory()->create(['role' => Role::User]);
        $plan = SubscriptionPlan::factory()->create(['price' => 30]);

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan));
        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->get(route('user.subscription.show'))
            ->assertStatus(200)
            ->assertSee('How to pay')
            ->assertSee($transaction->reference());
    }

    public function test_a_trial_subscriber_owes_nothing_and_is_not_shown_payment_instructions(): void
    {
        $this->configureBankDetails();

        $user = User::factory()->create(['role' => Role::User]);
        $plan = SubscriptionPlan::factory()->withTrial(14)->create();

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan));

        $this->actingAs($user)->get(route('user.subscription.show'))
            ->assertStatus(200)
            ->assertDontSee('How to pay');
    }

    public function test_accounts_sees_the_same_reference_the_buyer_was_given(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $plan = SubscriptionPlan::factory()->create(['price' => 30]);

        $this->actingAs($user)->post(route('user.plans.subscribe', $plan));
        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($accounts)->get(route('accounts.transactions.index'))
            ->assertStatus(200)
            ->assertSee($transaction->reference());
    }

    public function test_the_reference_is_stable_and_uses_the_configured_prefix(): void
    {
        config(['payments.reference_prefix' => 'SHOP']);

        $user = User::factory()->create(['role' => Role::User]);
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => SubscriptionPlan::factory()->create()->id,
            'status' => SubscriptionStatus::Pending,
            'starts_at' => now(),
        ]);
        $transaction = $subscription->transactions()->create([
            'user_id' => $user->id,
            'amount' => 10,
            'gateway' => 'manual',
            'status' => TransactionStatus::Pending,
        ]);

        $expected = 'SHOP-'.str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);

        $this->assertSame($expected, $transaction->reference());
        // Derived from the key, so it survives a round-trip unchanged.
        $this->assertSame($expected, $transaction->fresh()->reference());
    }

    public function test_amounts_render_in_the_configured_currency(): void
    {
        config(['payments.currency_symbol' => '₹']);

        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['price' => 1499.50]);

        $this->actingAs($user)->get(route('user.courses.show', $course))
            ->assertStatus(200)
            ->assertSee('₹1,499.50');
    }
}
