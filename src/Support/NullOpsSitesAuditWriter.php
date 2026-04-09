<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use YezzMedia\OpsSites\Contracts\OpsSitesAuditWriter;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;

final class NullOpsSitesAuditWriter implements OpsSitesAuditWriter
{
    public function record(SitesPostureRefreshed $event): void {}
}
