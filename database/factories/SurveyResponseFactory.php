<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'token' => SurveyResponse::generateToken(),
            'started_at' => null,
            'completed_at' => null,
            'meta' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }
}
