<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Listeners;

use YezzMedia\OpsSites\Contracts\OpsSitesAuditWriter;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;

final class OpsSitesAuditListener
{
    public function __construct(private readonly OpsSitesAuditWriter $writer) {}

    public function handleSitesPostureRefreshed(SitesPostureRefreshed $event): void
    {
        $this->writer->record($event);
    }
}
