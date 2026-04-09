<?php

declare(strict_types=1);

use YezzMedia\Foundation\Registry\FeatureRegistry;
use YezzMedia\Foundation\Registry\OpsModuleRegistry;
use YezzMedia\Foundation\Registry\PackageRegistry;
use YezzMedia\Foundation\Registry\PermissionRegistry;
use YezzMedia\OpsSites\OpsSitesPlatformPackage;

it('registers the ops sites package surface', function (): void {
    expect(app(PackageRegistry::class)->has('yezzmedia/laravel-ops-sites'))->toBeTrue()
        ->and(app(PermissionRegistry::class)->forPackage('yezzmedia/laravel-ops-sites')->pluck('name')->all())->toBe([
            'ops.sites.view',
            'ops.sites.manage',
        ])
        ->and(app(FeatureRegistry::class)->forPackage('yezzmedia/laravel-ops-sites')->pluck('name')->all())->toBe([
            'sites.inventory',
            'sites.domain_posture',
            'sites.ssl_assignment',
            'sites.infrastructure_assignment',
        ])
        ->and(app(OpsModuleRegistry::class)->forPackage('yezzmedia/laravel-ops-sites')->pluck('key')->all())->toBe([
            'diagnostics.sites.overview',
            'diagnostics.sites.detail',
        ]);
});

it('describes the approved ops sites package surface', function (): void {
    $package = new OpsSitesPlatformPackage;

    expect($package->metadata()->name)->toBe('yezzmedia/laravel-ops-sites')
        ->and($package->permissionDefinitions())->toHaveCount(2)
        ->and($package->featureDefinitions())->toHaveCount(4)
        ->and($package->auditEventDefinitions())->toHaveCount(3)
        ->and($package->installSteps())->toHaveCount(3)
        ->and($package->doctorChecks())->toHaveCount(4)
        ->and($package->opsModuleDefinitions())->toHaveCount(2);
});
