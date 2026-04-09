<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use InvalidArgumentException;
use Spatie\Activitylog\Support\ActivityLogger;
use YezzMedia\OpsSites\Contracts\OpsSitesAuditWriter;
use YezzMedia\OpsSites\Events\SiteMutated;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;

final class ActivityLogOpsSitesAuditWriter implements OpsSitesAuditWriter
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function record(object $event): void
    {
        if ($event instanceof SitesPostureRefreshed) {
            $this->recordSitesPostureRefreshed($event);

            return;
        }

        if ($event instanceof SiteMutated) {
            $this->recordSiteMutated($event);

            return;
        }

        throw new InvalidArgumentException(sprintf('Unsupported ops sites audit event [%s].', $event::class));
    }

    private function recordSitesPostureRefreshed(SitesPostureRefreshed $event): void
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

    private function recordSiteMutated(SiteMutated $event): void
    {
        $logger = $this->activity
            ->useLog(config('ops-sites.audit.log_name', 'ops-sites'))
            ->event($event->action)
            ->withProperties([
                'site_key' => $event->siteKey,
                'site_name' => $event->siteName,
                'lifecycle_status' => $event->lifecycleStatus,
                'domains' => $event->domains,
                'assignment_status' => $event->assignmentStatus,
                'assignment_target' => $event->assignmentTarget,
                'actor_id' => $event->actorId,
                'source' => $event->source,
                'completed_at' => $event->completedAt,
            ]);

        $logger->log(match ($event->action) {
            'created' => 'Ops site was created.',
            'updated' => 'Ops site was updated.',
            'archived' => 'Ops site was archived.',
            default => 'Ops site was changed.',
        });
    }
}
