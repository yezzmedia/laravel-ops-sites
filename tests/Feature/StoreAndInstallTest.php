<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\OpsSites\Doctor\SitesStoreReadyCheck;
use YezzMedia\OpsSites\Install\EnsureOpsSitesStoreReadyInstallStep;
use YezzMedia\OpsSites\Support\OpsSitesStoreSetup;

it('exposes the real package migration path', function (): void {
    $storeSetup = app(OpsSitesStoreSetup::class);

    expect($storeSetup->migrationPath())->toBe('/home/yezz/Developement/packages/laravel-ops-sites/database/migrations');
});

it('reports the store as not ready when a required table is missing', function (): void {
    Schema::drop('ops_site_assignments');

    $result = app(SitesStoreReadyCheck::class)->run();

    expect($result->status)->toBe('failed')
        ->and($result->context['missing_tables'])->toBe(['ops_site_assignments']);
});

it('refuses to run store migrations when migrations are disabled', function (): void {
    Schema::drop('ops_site_assignments');

    $step = app(EnsureOpsSitesStoreReadyInstallStep::class);

    expect(fn () => $step->handle(new InstallContext(allowMigrations: false)))->toThrow(
        RuntimeException::class,
        'Ops sites store has a partial table set. Resolve the partial state before continuing.',
    );
});

it('reports a partial store when only some required tables exist', function (): void {
    Schema::drop('ops_site_assignments');

    $storeSetup = app(OpsSitesStoreSetup::class);

    expect($storeSetup->hasPartialTables())->toBeTrue()
        ->and($storeSetup->storeReady())->toBeFalse();
});
