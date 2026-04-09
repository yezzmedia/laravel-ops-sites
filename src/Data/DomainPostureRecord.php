<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Data;

use YezzMedia\OpsSites\Support\DomainPostureStatus;

final readonly class DomainPostureRecord
{
    public function __construct(
        public string $domain,
        public DomainPostureStatus $status,
        public string $message,
        public bool $isPrimary = false,
    ) {}
}
