<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Data;

use YezzMedia\OpsSites\Support\SiteLifecycleStatus;

final readonly class SiteLifecycleSummary
{
    public function __construct(
        public SiteLifecycleStatus $status,
        public string $message,
    ) {}
}
