<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

enum SiteLifecycleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Provisioning = 'provisioning';
    case Archived = 'archived';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Provisioning => 'Provisioning',
            self::Archived => 'Archived',
            self::Unknown => 'Unknown',
        };
    }
}
