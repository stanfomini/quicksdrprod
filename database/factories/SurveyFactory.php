<?php

namespace Database\Factories;

use App\Enums\SurveyAudience;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(3);

        return [
            'slug' => Str::slug($title).'-'.Str::random(6),
            'title' => $title,
            'description' => $this->faker->optional()->paragraph(),
            'audience' => $this->faker->randomElement(SurveyAudience::cases()),
            'settings' => null,
            'published_at' => now(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['published_at' => null]);
    }

    public function audience(SurveyAudience $audience): static
    {
        return $this->state(fn (): array => ['audience' => $audience]);
    }
}
