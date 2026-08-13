<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advertisement>
 */
class AdvertisementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->words(4, true)),
            'banner_path' => null,
            'target_url' => fake()->url(),
            'status' => ContentStatus::Draft,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ContentStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => ContentStatus::Approved,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}
