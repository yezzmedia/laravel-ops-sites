<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Contracts;

interface OpsSitesAuditWriter
{
    public function record(object $event): void;
}
