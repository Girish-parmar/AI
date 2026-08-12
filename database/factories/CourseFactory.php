<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['Programming', 'Design', 'Business', 'Marketing', 'Music']),
            'price' => fake()->randomFloat(2, 9, 199),
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
