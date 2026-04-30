<?php

namespace App\Enums;

enum SurveyQuestionType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Name = 'name';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Url = 'url';
    case Date = 'date';
    case DateTime = 'datetime';
    case Time = 'time';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Select = 'select';
    case YesNo = 'yes_no';
    case Scale = 'scale';
    case Rating = 'rating';
    case FileUpload = 'file_upload';
    case Section = 'section';
    case Description = 'description';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Short text',
            self::Textarea => 'Long text',
            self::Name => 'Name',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Number => 'Number',
            self::Url => 'URL / website',
            self::Date => 'Date',
            self::DateTime => 'Date & time',
            self::Time => 'Time',
            self::Radio => 'Single choice (radio)',
            self::Checkbox => 'Multi choice (checkbox)',
            self::Select => 'Dropdown',
            self::YesNo => 'Yes / no',
            self::Scale => 'Scale (1-N)',
            self::Rating => 'Star rating',
            self::FileUpload => 'File upload',
            self::Section => '— Section divider —',
            self::Description => '— Description text —',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Text, self::Textarea, self::Name, self::Email, self::Phone, self::Number, self::Url => 'Text inputs',
            self::Date, self::DateTime, self::Time => 'Date & time',
            self::Radio, self::Checkbox, self::Select, self::YesNo => 'Choice',
            self::Scale, self::Rating => 'Numeric',
            self::FileUpload => 'File',
            self::Section, self::Description => 'Layout',
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

    public function isAnswerable(): bool
    {
        return ! in_array($this, [self::Section, self::Description], true);
    }

    public function defaultPlaceholder(): ?string
    {
        return match ($this) {
            self::Email => 'name@example.com',
            self::Phone => '+1 555 123 4567',
            self::Url => 'https://example.com',
            self::Name => 'Jane Doe',
            default => null,
        };
    }
}
