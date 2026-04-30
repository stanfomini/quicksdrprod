<?php

namespace App\Models;

use App\Enums\SurveyQuestionType;
use Database\Factories\SurveyQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyQuestion extends Model
{
    /** @use HasFactory<SurveyQuestionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => SurveyQuestionType::class,
            'options' => 'array',
            'required' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        $rules = [$this->required ? 'required' : 'nullable'];

        $max = (int) ($this->options['max'] ?? 5);

        match ($this->type) {
            SurveyQuestionType::Text => array_push($rules, 'string', 'max:500'),
            SurveyQuestionType::Textarea => array_push($rules, 'string', 'max:5000'),
            SurveyQuestionType::Date => $rules[] = 'date',
            SurveyQuestionType::Scale, SurveyQuestionType::Rating => array_push($rules, 'integer', 'min:1', 'max:'.$max),
            SurveyQuestionType::Radio, SurveyQuestionType::Select => array_push($rules, 'string', 'in:'.implode(',', $this->optionValues())),
            SurveyQuestionType::Checkbox => $rules[] = 'array',
        };

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    public function optionValues(): array
    {
        $choices = $this->options['choices'] ?? [];

        return array_values(array_map(static fn ($choice): string => (string) ($choice['value'] ?? $choice), $choices));
    }
}
