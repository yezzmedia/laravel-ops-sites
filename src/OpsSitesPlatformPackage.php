<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites;

use YezzMedia\Foundation\Contracts\DefinesAuditEvents;
use YezzMedia\Foundation\Contracts\DefinesInstallSteps;
use YezzMedia\Foundation\Contracts\DefinesPermissions;
use YezzMedia\Foundation\Contracts\PlatformPackage;
use YezzMedia\Foundation\Contracts\ProvidesDoctorChecks;
use YezzMedia\Foundation\Contracts\ProvidesOpsModules;
use YezzMedia\Foundation\Contracts\RegistersFeatures;
use YezzMedia\Foundation\Data\AuditEventDefinition;
use YezzMedia\Foundation\Data\FeatureDefinition;
use YezzMedia\Foundation\Data\OpsModuleDefinition;
use YezzMedia\Foundation\Data\PackageMetadata;
use YezzMedia\Foundation\Data\PermissionDefinition;
use YezzMedia\Foundation\Doctor\DoctorCheck;
use YezzMedia\Foundation\Install\InstallStep;
use YezzMedia\OpsSites\Doctor\DnsTargetsResolvableCheck;
use YezzMedia\OpsSites\Doctor\PrimaryDomainAssignedCheck;
use YezzMedia\OpsSites\Doctor\SiteAssignmentsConfiguredCheck;
use YezzMedia\OpsSites\Doctor\SitesStoreReadyCheck;
use YezzMedia\OpsSites\Install\ConfigureOpsSitesAuditInstallStep;
use YezzMedia\OpsSites\Install\EnsureOpsSitesStoreReadyInstallStep;
use YezzMedia\OpsSites\Install\PublishOpsSitesMigrationsInstallStep;

final class OpsSitesPlatformPackage implements DefinesAuditEvents, DefinesInstallSteps, DefinesPermissions, PlatformPackage, ProvidesDoctorChecks, ProvidesOpsModules, RegistersFeatures
{
    public function metadata(): PackageMetadata
    {
        return new PackageMetadata(
            name: 'yezzmedia/laravel-ops-sites',
            vendor: 'yezzmedia',
            description: 'Ops-facing site inventory, domain posture, and assignment visibility package for the Yezz Media Laravel platform.',
            packageClass: self::class,
        );
    }

    /**
     * @return array<int, PermissionDefinition>
     */
    public function permissionDefinitions(): array
    {
        return [
            new PermissionDefinition(
                name: 'ops.sites.view',
                package: 'yezzmedia/laravel-ops-sites',
                label: 'View ops sites',
                description: 'Allows viewing site inventory, domain posture, and assignment visibility.',
                defaultRoleHints: ['super-admin'],
            ),
            new PermissionDefinition(
                name: 'ops.sites.manage',
                package: 'yezzmedia/laravel-ops-sites',
                label: 'Manage ops sites',
                description: 'Allows refreshing site posture and running package-owned site actions.',
                defaultRoleHints: ['super-admin'],
            ),
        ];
    }

    /**
     * @return array<int, FeatureDefinition>
     */
    public function featureDefinitions(): array
    {
        return [
            new FeatureDefinition('sites.inventory', 'yezzmedia/laravel-ops-sites', 'Site inventory', 'Provides an ops-facing inventory of managed sites.'),
            new FeatureDefinition('sites.domain_posture', 'yezzmedia/laravel-ops-sites', 'Domain posture', 'Reports the technical posture of mapped domains and DNS targets.'),
            new FeatureDefinition('sites.ssl_assignment', 'yezzmedia/laravel-ops-sites', 'SSL assignment visibility', 'Shows certificate reference assignments at the site edge.'),
            new FeatureDefinition('sites.infrastructure_assignment', 'yezzmedia/laravel-ops-sites', 'Infrastructure assignment', 'Shows how sites are mapped onto infrastructure targets.'),
        ];
    }

    /**
     * @return array<int, AuditEventDefinition>
     */
    public function auditEventDefinitions(): array
    {
        return [
            new AuditEventDefinition(
                key: 'ops.sites.posture_refreshed',
                package: 'yezzmedia/laravel-ops-sites',
                action: 'refreshed',
                subjectType: 'site_posture_snapshot',
                description: 'Ops sites posture snapshot was refreshed.',
                severity: 'info',
                contextKeys: ['site_count', 'drifted_domains', 'warning_domains', 'actor_id', 'source'],
            ),
            new AuditEventDefinition(
                key: 'ops.sites.assignment_updated',
                package: 'yezzmedia/laravel-ops-sites',
                action: 'updated',
                subjectType: 'site_assignment',
                description: 'A site infrastructure assignment was updated.',
                severity: 'info',
                contextKeys: ['site_key', 'assignment_status', 'actor_id'],
            ),
            new AuditEventDefinition(
                key: 'ops.sites.domain_mapping_updated',
                package: 'yezzmedia/laravel-ops-sites',
                action: 'updated',
                subjectType: 'site_domain_mapping',
                description: 'A site domain mapping was updated.',
                severity: 'info',
                contextKeys: ['site_key', 'domain', 'actor_id'],
            ),
        ];
    }

    /**
     * @return array<int, InstallStep>
     */
    public function installSteps(): array
    {
        return [
            app(PublishOpsSitesMigrationsInstallStep::class),
            app(EnsureOpsSitesStoreReadyInstallStep::class),
            app(ConfigureOpsSitesAuditInstallStep::class),
        ];
    }

    /**
     * @return array<int, DoctorCheck>
     */
    public function doctorChecks(): array
    {
        return [
            app(SitesStoreReadyCheck::class),
            app(PrimaryDomainAssignedCheck::class),
            app(DnsTargetsResolvableCheck::class),
            app(SiteAssignmentsConfiguredCheck::class),
        ];
    }

    /**
     * @return array<int, OpsModuleDefinition>
     */
    public function opsModuleDefinitions(): array
    {
        return [
            new OpsModuleDefinition(
                key: 'diagnostics.sites.overview',
                package: 'yezzmedia/laravel-ops-sites',
                label: 'Sites Overview',
                type: 'page',
                permissionHint: 'ops.sites.view',
            ),
            new OpsModuleDefinition(
                key: 'diagnostics.sites.detail',
                package: 'yezzmedia/laravel-ops-sites',
                label: 'Site Detail',
                type: 'page',
                permissionHint: 'ops.sites.view',
            ),
        ];
    }
}
