<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use YezzMedia\OpsSites\Actions\RefreshSitesPostureAction;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;
use YezzMedia\OpsSites\Models\OpsSite;

it('dispatches a sites posture refreshed event on refresh', function (): void {
    Event::fake([SitesPostureRefreshed::class]);

    OpsSite::query()->create([
        'site_key' => 'alpha',
        'name' => 'Alpha Site',
        'lifecycle_status' => 'active',
    ]);

    app(RefreshSitesPostureAction::class)->execute('test');

    Event::assertDispatched(SitesPostureRefreshed::class, function (SitesPostureRefreshed $event): bool {
        return $event->siteCount === 1
            && $event->source === 'test'
            && $event->warningCount >= 0
            && $event->driftedCount >= 0
            && $event->failingCount >= 0
            && $event->completedAt !== '';
    });
});
