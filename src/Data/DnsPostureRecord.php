<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Data;

use YezzMedia\OpsSites\Support\DomainPostureStatus;

final readonly class DnsPostureRecord
{
    /**
     * @param  array<int, string>  $expectedTargets
     * @param  array<int, string>  $resolvedTargets
     */
    public function __construct(
        public string $domain,
        public DomainPostureStatus $status,
        public array $expectedTargets,
        public array $resolvedTargets,
        public string $message,
    ) {}
}
