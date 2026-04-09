<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Events;

final readonly class SiteMutated
{
    /**
     * @param  array<int, string>  $domains
     */
    public function __construct(
        public string $action,
        public string $siteKey,
        public string $siteName,
        public string $lifecycleStatus,
        public array $domains,
        public string $assignmentStatus,
        public ?string $assignmentTarget,
        public ?int $actorId,
        public string $source,
        public string $completedAt,
    ) {}
}
