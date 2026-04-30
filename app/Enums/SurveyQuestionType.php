<?php

namespace App\Enums;

enum SurveyQuestionType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Select = 'select';
    case Scale = 'scale';
    case Rating = 'rating';
    case Date = 'date';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Short text',
            self::Textarea => 'Long text',
            self::Radio => 'Single choice (radio)',
            self::Checkbox => 'Multi choice (checkbox)',
            self::Select => 'Dropdown',
            self::Scale => 'Scale (1-N)',
            self::Rating => 'Star rating',
            self::Date => 'Date',
        };
    }

    public function needsOptions(): bool
    {
        return in_array($this, [self::Radio, self::Checkbox, self::Select], true);
    }

    public function valueIsArray(): bool
    {
        return $this === self::Checkbox;
    }
}
