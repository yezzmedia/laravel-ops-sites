<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Contracts;

use YezzMedia\OpsSites\Events\SitesPostureRefreshed;

interface OpsSitesAuditWriter
{
    public function record(SitesPostureRefreshed $event): void;
}
