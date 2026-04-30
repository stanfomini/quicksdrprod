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
            'visible_when' => 'array',
            'required' => 'boolean',
            'starts_new_step' => 'boolean',
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
        if (! $this->type->isAnswerable()) {
            return ['nullable'];
        }

        $rules = [$this->required ? 'required' : 'nullable'];
        $max = (int) ($this->options['max'] ?? 5);

        match ($this->type) {
            SurveyQuestionType::Text, SurveyQuestionType::Name => array_push($rules, 'string', 'max:255'),
            SurveyQuestionType::Textarea => array_push($rules, 'string', 'max:5000'),
            SurveyQuestionType::Email => array_push($rules, 'string', 'email:rfc', 'max:255'),
            SurveyQuestionType::Phone => array_push($rules, 'string', 'max:30'),
            SurveyQuestionType::Number => $rules[] = 'numeric',
            SurveyQuestionType::Url => array_push($rules, 'string', 'url', 'max:1000'),
            SurveyQuestionType::Date, SurveyQuestionType::DateTime => $rules[] = 'date',
            SurveyQuestionType::Time => array_push($rules, 'string', 'date_format:H:i'),
            SurveyQuestionType::YesNo => array_push($rules, 'string', 'in:yes,no'),
            SurveyQuestionType::Scale, SurveyQuestionType::Rating => array_push($rules, 'integer', 'min:1', 'max:'.$max),
            SurveyQuestionType::Radio, SurveyQuestionType::Select => array_push($rules, 'string', 'in:'.implode(',', $this->optionValues() ?: ['__none__'])),
            SurveyQuestionType::Checkbox => $rules[] = 'array',
            SurveyQuestionType::FileUpload => array_push($rules, 'file', 'max:10240'),
            SurveyQuestionType::Section, SurveyQuestionType::Description => null,
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

    /**
     * Decide whether this question should be visible given the current answers.
     *
     * @param  array<int|string, mixed>  $answers  keyed by question id
     */
    public function isVisible(array $answers): bool
    {
        $rule = $this->visible_when;
        if (! is_array($rule) || empty($rule['question_id'])) {
            return true;
        }

        $current = $answers[$rule['question_id']] ?? null;
        $expected = $rule['value'] ?? null;
        $operator = $rule['operator'] ?? 'equals';

        return match ($operator) {
            'equals' => (string) $current === (string) $expected,
            'not_equals' => (string) $current !== (string) $expected,
            'contains' => is_array($current) ? in_array($expected, $current, true) : str_contains((string) $current, (string) $expected),
            default => true,
        };
    }
}
