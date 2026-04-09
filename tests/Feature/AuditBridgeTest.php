<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use YezzMedia\OpsSites\Contracts\OpsSitesAuditWriter;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;
use YezzMedia\OpsSites\Support\ActivityLogOpsSitesAuditWriter;
use YezzMedia\OpsSites\Support\NullOpsSitesAuditWriter;

it('binds the null audit writer by default', function (): void {
    expect(app(OpsSitesAuditWriter::class))->toBeInstanceOf(NullOpsSitesAuditWriter::class);
});

it('null audit writer accepts sites posture events', function (): void {
    $writer = new NullOpsSitesAuditWriter;

    $writer->record(new SitesPostureRefreshed(
        siteCount: 1,
        warningCount: 0,
        driftedCount: 0,
        failingCount: 0,
        warningDomains: [],
        actorId: 7,
        source: 'test',
        completedAt: '2026-04-09T12:00:00+00:00',
    ));

    expect(true)->toBeTrue();
});

it('binds the activitylog audit writer when configured', function (): void {
    if (! class_exists(Activity::class)) {
        $this->markTestSkipped('spatie/laravel-activitylog is not installed in the package environment.');
    }

    if (! Schema::hasTable('activity_log')) {
        Schema::create('activity_log', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->json('attribute_changes')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    config()->set('ops-sites.audit.driver', 'activitylog');
    app()->forgetInstance(OpsSitesAuditWriter::class);

    $writer = app(OpsSitesAuditWriter::class);

    expect($writer)->toBeInstanceOf(ActivityLogOpsSitesAuditWriter::class);

    $writer->record(new SitesPostureRefreshed(
        siteCount: 3,
        warningCount: 1,
        driftedCount: 1,
        failingCount: 0,
        warningDomains: ['alpha.example.com'],
        actorId: 7,
        source: 'ops_panel',
        completedAt: '2026-04-09T12:30:00+00:00',
    ));

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->log_name)->toBe('ops-sites')
        ->and($activity?->event)->toBe('refreshed')
        ->and($activity?->description)->toBe('Ops sites posture snapshot was refreshed.')
        ->and($activity?->getProperty('site_count'))->toBe(3)
        ->and($activity?->getProperty('warning_domains'))->toBe(['alpha.example.com'])
        ->and($activity?->getProperty('source'))->toBe('ops_panel');
});
