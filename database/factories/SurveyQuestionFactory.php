<?php

namespace Database\Factories;

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'type' => SurveyQuestionType::Text,
            'label' => $this->faker->sentence(4),
            'help_text' => null,
            'options' => null,
            'required' => false,
            'order' => 0,
        ];
    }

    public function type(SurveyQuestionType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function required(): static
    {
        return $this->state(fn (): array => ['required' => true]);
    }

    /**
     * @param  array<int, string>  $choices
     */
    public function withChoices(array $choices): static
    {
        return $this->state(fn (): array => [
            'options' => [
                'choices' => array_map(static fn (string $c): array => ['value' => $c, 'label' => $c], $choices),
            ],
        ]);
    }
}
