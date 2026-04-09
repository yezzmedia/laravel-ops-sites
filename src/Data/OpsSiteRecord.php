<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Data;

use YezzMedia\OpsSites\Support\DomainPostureStatus;
use YezzMedia\OpsSites\Support\SiteLifecycleStatus;
use YezzMedia\OpsSites\Support\SslAssignmentStatus;

final readonly class OpsSiteRecord
{
    public function __construct(
        public string $key,
        public string $name,
        public SiteLifecycleStatus $lifecycleStatus,
        public DomainPostureStatus $domainStatus,
        public SslAssignmentStatus $sslStatus,
        public int $domainCount,
        public ?string $primaryDomain,
        public string $assignmentTarget,
    ) {}
}
