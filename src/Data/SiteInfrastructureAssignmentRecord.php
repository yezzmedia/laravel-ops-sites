<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Data;

use YezzMedia\OpsSites\Support\SiteAssignmentStatus;

final readonly class SiteInfrastructureAssignmentRecord
{
    public function __construct(
        public string $siteKey,
        public SiteAssignmentStatus $status,
        public ?string $target,
        public string $message,
    ) {}
}
