<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Pending = 'pending';
    case Provisioning = 'provisioning';
    case Ready = 'ready';
    case Failed = 'failed';
    case ExportOnly = 'export_only';
    case Destroyed = 'destroyed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Provisioning => 'Provisioning',
            self::Ready => 'Ready',
            self::Failed => 'Failed',
            self::ExportOnly => 'Export-only (cancelled)',
            self::Destroyed => 'Destroyed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::Provisioning => 'blue',
            self::Ready => 'green',
            self::Failed => 'red',
            self::ExportOnly => 'amber',
            self::Destroyed => 'zinc',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Destroyed], true);
    }
}
