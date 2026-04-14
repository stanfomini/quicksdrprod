<?php

namespace App\Enums;

enum TenantTier: string
{
    case Tier1 = 'tier1';
    case Tier2 = 'tier2';
    case Tier3 = 'tier3';
    case Tier4 = 'tier4';

    public function label(): string
    {
        return match ($this) {
            self::Tier1 => 'Self-serve',
            self::Tier2 => 'Done-for-you setup',
            self::Tier3 => 'Multi-location + training',
            self::Tier4 => 'Custom build',
        };
    }

    public function monthlyPriceCents(): int
    {
        return match ($this) {
            self::Tier1, self::Tier2, self::Tier3 => 35000, // $350/mo
            self::Tier4 => 0,                                // hourly invoicing
        };
    }

    public function setupFeeCents(): int
    {
        return match ($this) {
            self::Tier1 => 0,
            self::Tier2 => 100000,  // $1000 one-time
            self::Tier3 => 300000,  // $3000 one-time
            self::Tier4 => 0,       // hourly
        };
    }

    public function freeTrialMonths(): int
    {
        return match ($this) {
            self::Tier1 => 0,
            self::Tier2, self::Tier3 => 2, // first 2 months waived
            self::Tier4 => 0,
        };
    }

    /**
     * DigitalOcean droplet size slug for Forge server creation.
     */
    public function serverSize(): string
    {
        return match ($this) {
            self::Tier1, self::Tier2 => 's-2vcpu-2gb',       // $18/mo DO (closest to $50 Forge tier)
            self::Tier3 => 's-2vcpu-4gb',                    // $24/mo DO (closest to $100 Forge tier)
            self::Tier4 => 's-2vcpu-2gb',                    // fallback
        };
    }

    public function needsServer(): bool
    {
        return $this !== self::Tier4;
    }
}
