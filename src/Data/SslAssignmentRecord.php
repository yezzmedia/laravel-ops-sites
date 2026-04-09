<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Data;

use YezzMedia\OpsSites\Support\SslAssignmentStatus;

final readonly class SslAssignmentRecord
{
    public function __construct(
        public string $domain,
        public SslAssignmentStatus $status,
        public ?string $certificateReference,
        public string $message,
    ) {}
}
