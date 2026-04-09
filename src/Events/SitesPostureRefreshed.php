<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Events;

final readonly class SitesPostureRefreshed
{
    /**
     * @param  array<int, string>  $warningDomains
     */
    public function __construct(
        public int $siteCount,
        public int $warningCount,
        public int $driftedCount,
        public int $failingCount,
        public array $warningDomains,
        public ?int $actorId,
        public string $source,
        public string $completedAt,
    ) {}
}
