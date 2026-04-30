<?php

namespace Database\Factories;

use App\Enums\SurveyAudience;
use App\Enums\SurveyStatus;
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
            'status' => SurveyStatus::Published,
            'brand_color' => '#10b981',
            'show_progress_bar' => true,
            'notify_respondent' => false,
            'require_unique_email' => false,
            'settings' => null,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => SurveyStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function unpublished(): static
    {
        return $this->draft();
    }

    public function audience(SurveyAudience $audience): static
    {
        return $this->state(fn (): array => ['audience' => $audience]);
    }
}
