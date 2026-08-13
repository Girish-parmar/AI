<?php

namespace Database\Factories;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalDocument>
 */
class LegalDocumentFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(LegalDocumentType::cases());

        return [
            'type' => $type,
            'title' => $type->label(),
            'content' => fake()->paragraphs(5, true),
            'version' => '1.0',
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['published_at' => now()->subDay()]);
    }
}
