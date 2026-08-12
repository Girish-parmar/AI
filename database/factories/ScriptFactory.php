<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Script;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Script>
 */
class ScriptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['Automation', 'Data', 'DevOps', 'Web', 'Utilities']),
            'price' => fake()->randomFloat(2, 4, 99),
            'status' => ContentStatus::Draft,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ContentStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(['status' => ContentStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => ContentStatus::Rejected]);
    }
}
