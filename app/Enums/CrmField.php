<?php

namespace App\Enums;

enum CrmField: string
{
    case None = 'none';
    case Email = 'email';
    case Phone = 'phone';
    case Name = 'name';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::None => '— Not mapped —',
            self::Email => 'Email',
            self::Phone => 'Phone',
            self::Name => 'Name',
            self::Company => 'Company',
        };
    }

    public static function defaultForType(SurveyQuestionType $type): self
    {
        return match ($type) {
            SurveyQuestionType::Email => self::Email,
            SurveyQuestionType::Phone => self::Phone,
            SurveyQuestionType::Name => self::Name,
            default => self::None,
        };
    }
}
