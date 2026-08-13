<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Enums\Role;
use App\Models\Course;
use App\Models\Purchase;
use App\Models\Script;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression coverage for real findings from earlier Qodo PR reviews that
 * were flagged but never fixed at the time. Each test here reproduces the
 * exact scenario from the original finding.
 */
class PriorReviewFindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_repurchase_a_course_after_a_failed_purchase(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $course = Course::factory()->approved()->create();

        $this->actingAs($user)->post(route('user.courses.purchase', $course));
        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($accounts)->post(route('accounts.transactions.fail', $transaction));

        // The show page must offer the buy button again, and the endpoint
        // must accept a retry — both sides of the same guarantee.
        $this->actingAs($user)->get(route('user.courses.show', $course))
            ->assertStatus(200)
            ->assertSee('Buy this course');

        $this->actingAs($user)->post(route('user.courses.purchase', $course))
            ->assertRedirect(route('user.purchases.index'));

        $this->assertSame(2, Purchase::where('user_id', $user->id)->count());
    }

    public function test_a_user_can_repurchase_a_script_after_a_failed_purchase(): void
    {
        $user = User::factory()->create(['role' => Role::User]);
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $script = Script::factory()->approved()->create();

        $this->actingAs($user)->post(route('user.scripts.purchase', $script));
        $transaction = Transaction::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($accounts)->post(route('accounts.transactions.fail', $transaction));

        $this->actingAs($user)->get(route('user.scripts.show', $script))
            ->assertStatus(200)
            ->assertSee('Buy this script');

        $this->actingAs($user)->post(route('user.scripts.purchase', $script))
            ->assertRedirect(route('user.purchases.index'));
    }

    public function test_a_pending_purchase_still_blocks_the_buy_button(): void
    {
        // The fix must not over-correct: an *open* purchase (pending or
        // completed) still hides the buy form, matching purchase()'s guard.
        $user = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create();

        $this->actingAs($user)->post(route('user.courses.purchase', $course));

        $this->actingAs($user)->get(route('user.courses.show', $course))
            ->assertStatus(200)
            ->assertDontSee('Buy this course');

        $this->actingAs($user)->post(route('user.courses.purchase', $course))->assertStatus(422);
    }

    public function test_an_unrecognized_role_string_in_middleware_denies_rather_than_500s(): void
    {
        // Reproduces the exact scenario: a route accidentally declares a
        // role string that doesn't exist in the Role enum. Role::from()
        // would throw a ValueError (500); Role::tryFrom() must deny (403).
        Route::middleware(['web', 'auth', 'role:not-a-real-role'])
            ->get('/__test/typo-role-route', fn () => 'should never be reached');

        $user = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($user)->get('/__test/typo-role-route')->assertForbidden();
    }

    public function test_a_payout_cannot_be_recorded_against_a_non_creator(): void
    {
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $notACreator = User::factory()->create(['role' => Role::User]);

        $response = $this->actingAs($accounts)->post(route('accounts.payouts.store'), [
            'creator_id' => $notACreator->id,
            'amount' => 10,
        ]);

        $response->assertSessionHasErrors('creator_id');
        $this->assertDatabaseMissing('payouts', ['creator_id' => $notACreator->id]);
    }

    public function test_a_payout_for_exactly_the_full_outstanding_balance_is_allowed(): void
    {
        // The float-comparison bug could reject a request for exactly the
        // outstanding amount when summed decimals didn't round-trip
        // through a binary float cleanly. 33.33 (a non-terminating binary
        // fraction) exercises exactly that edge.
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = User::factory()->create(['role' => Role::Creator]);
        $buyer = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['creator_id' => $creator->id, 'price' => 33.33]);

        Purchase::create([
            'user_id' => $buyer->id,
            'purchasable_type' => Course::class,
            'purchasable_id' => $course->id,
            'price' => 33.33,
            'status' => PurchaseStatus::Completed,
        ]);

        $this->actingAs($accounts)->post(route('accounts.payouts.store'), [
            'creator_id' => $creator->id,
            'amount' => 33.33,
        ])->assertRedirect();

        $this->assertDatabaseHas('payouts', ['creator_id' => $creator->id, 'amount' => '33.33']);
    }

    public function test_a_payout_one_cent_over_the_outstanding_balance_is_still_rejected(): void
    {
        $accounts = User::factory()->create(['role' => Role::Accounts]);
        $creator = User::factory()->create(['role' => Role::Creator]);
        $buyer = User::factory()->create(['role' => Role::User]);
        $course = Course::factory()->approved()->create(['creator_id' => $creator->id, 'price' => 33.33]);

        Purchase::create([
            'user_id' => $buyer->id,
            'purchasable_type' => Course::class,
            'purchasable_id' => $course->id,
            'price' => 33.33,
            'status' => PurchaseStatus::Completed,
        ]);

        $this->actingAs($accounts)->post(route('accounts.payouts.store'), [
            'creator_id' => $creator->id,
            'amount' => 33.34,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('payouts', ['creator_id' => $creator->id]);
    }
}
