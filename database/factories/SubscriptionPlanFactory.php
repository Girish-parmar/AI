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
}
