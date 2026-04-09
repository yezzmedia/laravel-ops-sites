<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Data;

use Carbon\CarbonImmutable;
use YezzMedia\OpsSites\Support\DomainPostureStatus;

final readonly class OpsSiteSummary
{
    /**
     * @param  array<int, OpsSiteRecord>  $sites
     * @param  array<int, DomainPostureRecord>  $warningDomains
     */
    public function __construct(
        public DomainPostureStatus $overallStatus,
        public int $siteCount,
        public int $healthyCount,
        public int $warningCount,
        public int $driftedCount,
        public int $failingCount,
        public CarbonImmutable $completedAt,
        public array $sites,
        public array $warningDomains,
    ) {}
}
