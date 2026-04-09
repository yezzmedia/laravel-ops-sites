<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use Spatie\Activitylog\Support\ActivityLogger;
use YezzMedia\OpsSites\Contracts\OpsSitesAuditWriter;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;

final class ActivityLogOpsSitesAuditWriter implements OpsSitesAuditWriter
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function record(SitesPostureRefreshed $event): void
    {
        $logger = $this->activity
            ->useLog(config('ops-sites.audit.log_name', 'ops-sites'))
            ->event('refreshed')
            ->withProperties([
                'site_count' => $event->siteCount,
                'warning_count' => $event->warningCount,
                'drifted_count' => $event->driftedCount,
                'failing_count' => $event->failingCount,
                'warning_domains' => $event->warningDomains,
                'actor_id' => $event->actorId,
                'source' => $event->source,
                'completed_at' => $event->completedAt,
            ]);

        $logger->log('Ops sites posture snapshot was refreshed.');
    }
}
