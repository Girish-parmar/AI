<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_subscription_plan(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $response = $this->actingAs($admin)->post(route('admin.subscription-plans.store'), [
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'description' => 'For power users.',
            'price' => 29.99,
            'billing_interval' => 'monthly',
            'trial_days' => 14,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.subscription-plans.index'));
        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'pro-plan',
            'price' => '29.99',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_deactivate_a_plan_by_unchecking_is_active(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $this->actingAs($admin)->put(route('admin.subscription-plans.update', $plan), [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price' => $plan->price,
            'billing_interval' => $plan->billing_interval->value,
            'trial_days' => $plan->trial_days,
            // is_active omitted, like an unchecked checkbox
        ]);

        $this->assertFalse($plan->fresh()->is_active);
    }

    public function test_a_plan_slug_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        SubscriptionPlan::factory()->create(['slug' => 'pro-plan']);

        $response = $this->actingAs($admin)->post(route('admin.subscription-plans.store'), [
            'name' => 'Another Pro Plan',
            'slug' => 'pro-plan',
            'price' => 19.99,
            'billing_interval' => 'monthly',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_admin_can_delete_a_plan_with_no_subscriptions(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $plan = SubscriptionPlan::factory()->create();

        $this->actingAs($admin)->delete(route('admin.subscription-plans.destroy', $plan))
            ->assertRedirect(route('admin.subscription-plans.index'));

        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    }

    public function test_a_plan_with_subscriptions_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $plan = SubscriptionPlan::factory()->create();
        $user = User::factory()->create(['role' => Role::User]);

        Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
        ]);

        $this->actingAs($admin)->delete(route('admin.subscription-plans.destroy', $plan))
            ->assertStatus(422);

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }

    public function test_only_active_plans_are_offered_to_users(): void
    {
        $active = SubscriptionPlan::factory()->create();
        $inactive = SubscriptionPlan::factory()->inactive()->create();
        $user = User::factory()->create(['role' => Role::User]);

        $this->actingAs($user)->get(route('user.plans.index'))
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);

        $this->actingAs($user)->post(route('user.plans.subscribe', $inactive))->assertStatus(404);
    }
}
