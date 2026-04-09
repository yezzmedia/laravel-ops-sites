<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

enum SslAssignmentStatus: string
{
    case Assigned = 'assigned';
    case Missing = 'missing';
    case Mismatch = 'mismatch';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Missing => 'Missing',
            self::Mismatch => 'Mismatch',
            self::Unknown => 'Unknown',
        };
    }
}
