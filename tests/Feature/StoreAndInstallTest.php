<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\OpsSites\Doctor\SitesStoreReadyCheck;
use YezzMedia\OpsSites\Install\ConfigureOpsSitesAuditInstallStep;
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

it('returns without throwing when migrations are disabled on a partial store', function (): void {
    Schema::drop('ops_site_assignments');

    $step = app(EnsureOpsSitesStoreReadyInstallStep::class);

    expect(fn () => $step->handle(new InstallContext(allowMigrations: false)))->not->toThrow(
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

it('publishes the host config before configuring the sites audit driver', function (): void {
    $path = config_path('ops-sites.php');

    @unlink($path);
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    $step = app(ConfigureOpsSitesAuditInstallStep::class);

    $step->handle(new InstallContext(auditPackages: ['yezzmedia/laravel-ops-sites']));

    expect($path)->toBeFile()
        ->and(file_get_contents($path))->toContain("'driver' => env('OPS_SITES_AUDIT_DRIVER', 'activitylog'),");
});

it('accepts an already configured sites audit driver in the published host config', function (): void {
    $path = config_path('ops-sites.php');

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    file_put_contents($path, <<<'PHP'
<?php

return [
    'audit' => [
        'driver' => env('OPS_SITES_AUDIT_DRIVER', 'activitylog'),
    ],
];
PHP);

    $step = app(ConfigureOpsSitesAuditInstallStep::class);

    $step->handle(new InstallContext(auditPackages: ['yezzmedia/laravel-ops-sites']));

    expect(file_get_contents($path))->toContain("'driver' => env('OPS_SITES_AUDIT_DRIVER', 'activitylog'),");
});
