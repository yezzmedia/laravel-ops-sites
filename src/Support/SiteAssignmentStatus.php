<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

enum SiteAssignmentStatus: string
{
    case Assigned = 'assigned';
    case Missing = 'missing';
    case Ambiguous = 'ambiguous';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Missing => 'Missing',
            self::Ambiguous => 'Ambiguous',
            self::Unknown => 'Unknown',
        };
    }
}
