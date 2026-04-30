<?php

namespace App\Enums;

enum SurveyAudience: string
{
    case Agent = 'agent';
    case Prospect = 'prospect';
    case Tenant = 'tenant';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::Agent => 'Agent (post-call QA)',
            self::Prospect => 'Prospect / lead (outbound)',
            self::Tenant => 'Tenant / customer',
            self::Internal => 'Internal team',
        };
    }
}
