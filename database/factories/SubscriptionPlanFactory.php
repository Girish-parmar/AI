<?php

namespace Database\Factories;

use App\Enums\BillingInterval;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Basic', 'Pro', 'Team', 'Enterprise']).' Plan';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 5, 99),
            'billing_interval' => BillingInterval::Monthly,
            // Explicit rather than relying on the DB-level default (0) —
            // that default never reflects onto this in-memory instance
            // until refetched, same footgun UserFactory::role hit earlier.
            'trial_days' => 0,
            'is_active' => true,
        ];
    }

    public function yearly(): static
    {
        return $this->state(['billing_interval' => BillingInterval::Yearly]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withTrial(int $days = 14): static
    {
        return $this->state(['trial_days' => $days]);
    }
}
