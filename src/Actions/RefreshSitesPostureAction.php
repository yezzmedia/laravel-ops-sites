<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;
use YezzMedia\OpsSites\Support\OpsSitesManager;

final class RefreshSitesPostureAction
{
    public function __construct(
        private readonly OpsSitesManager $manager,
        private readonly Dispatcher $events,
    ) {}

    public function execute(string $source = 'manual'): void
    {
        $summary = $this->manager->refresh();

        $this->events->dispatch(new SitesPostureRefreshed(
            siteCount: $summary->siteCount,
            warningCount: $summary->warningCount,
            driftedCount: $summary->driftedCount,
            failingCount: $summary->failingCount,
            warningDomains: array_map(fn ($record): string => $record->domain, $summary->warningDomains),
            actorId: Auth::id(),
            source: $source,
            completedAt: $summary->completedAt->toIso8601String(),
        ));
    }
}
