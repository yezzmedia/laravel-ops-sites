<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

enum DomainPostureStatus: string
{
    case Healthy = 'healthy';
    case Warning = 'warning';
    case Drifted = 'drifted';
    case Failed = 'failed';
    case Unsupported = 'unsupported';

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::Warning => 'Warning',
            self::Drifted => 'Drifted',
            self::Failed => 'Failed',
            self::Unsupported => 'Unsupported',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Healthy => 'success',
            self::Warning => 'warning',
            self::Drifted, self::Failed => 'danger',
            self::Unsupported => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Healthy => 'heroicon-o-check-circle',
            self::Warning => 'heroicon-o-exclamation-triangle',
            self::Drifted => 'heroicon-o-arrows-right-left',
            self::Failed => 'heroicon-o-x-circle',
            self::Unsupported => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * @param  array<int, self>  $statuses
     */
    public static function worst(array $statuses): self
    {
        foreach ([self::Failed, self::Drifted, self::Warning, self::Unsupported, self::Healthy] as $status) {
            if (in_array($status, $statuses, true)) {
                return $status;
            }
        }

        return self::Unsupported;
    }
}
