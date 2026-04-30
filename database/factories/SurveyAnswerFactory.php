<?php

namespace Database\Factories;

use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyAnswer>
 */
class SurveyAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_response_id' => SurveyResponse::factory(),
            'survey_question_id' => SurveyQuestion::factory(),
            'value' => ['answer' => $this->faker->sentence()],
        ];
    }
}
