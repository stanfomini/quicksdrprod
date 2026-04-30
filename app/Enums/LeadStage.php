<?php

namespace App\Enums;

enum LeadStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal sent',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'sky',
            self::Qualified => 'amber',
            self::Proposal => 'violet',
            self::Won => 'emerald',
            self::Lost => 'zinc',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::New => 1,
            self::Contacted => 2,
            self::Qualified => 3,
            self::Proposal => 4,
            self::Won => 5,
            self::Lost => 99,
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Won, self::Lost], true);
    }
}
