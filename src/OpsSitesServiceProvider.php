<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Spatie\Activitylog\Support\ActivityLogger;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use YezzMedia\Foundation\Support\PlatformPackageRegistrar;
use YezzMedia\OpsSites\Actions\MutateSiteAction;
use YezzMedia\OpsSites\Actions\RefreshSitesPostureAction;
use YezzMedia\OpsSites\Contracts\OpsSitesAuditWriter;
use YezzMedia\OpsSites\Doctor\DnsTargetsResolvableCheck;
use YezzMedia\OpsSites\Doctor\PrimaryDomainAssignedCheck;
use YezzMedia\OpsSites\Doctor\SiteAssignmentsConfiguredCheck;
use YezzMedia\OpsSites\Doctor\SitesStoreReadyCheck;
use YezzMedia\OpsSites\Events\SiteMutated;
use YezzMedia\OpsSites\Events\SitesPostureRefreshed;
use YezzMedia\OpsSites\Install\ConfigureOpsSitesAuditInstallStep;
use YezzMedia\OpsSites\Install\EnsureOpsSitesStoreReadyInstallStep;
use YezzMedia\OpsSites\Install\PublishOpsSitesMigrationsInstallStep;
use YezzMedia\OpsSites\Listeners\OpsSitesAuditListener;
use YezzMedia\OpsSites\Support\ActivityLogOpsSitesAuditWriter;
use YezzMedia\OpsSites\Support\DnsPostureResolver;
use YezzMedia\OpsSites\Support\DomainPostureResolver;
use YezzMedia\OpsSites\Support\NullOpsSitesAuditWriter;
use YezzMedia\OpsSites\Support\OpsSitesManager;
use YezzMedia\OpsSites\Support\OpsSitesStoreSetup;
use YezzMedia\OpsSites\Support\SiteInfrastructureAssignmentResolver;
use YezzMedia\OpsSites\Support\SiteInventoryResolver;
use YezzMedia\OpsSites\Support\SslAssignmentResolver;

class OpsSitesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ops-sites')
            ->hasConfigFile('ops-sites')
            ->hasMigrations(['0001_create_ops_sites_tables']);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(OpsSitesAuditWriter::class, fn (): OpsSitesAuditWriter => $this->makeAuditWriter());

        $this->app->singleton(OpsSitesStoreSetup::class);

        $this->app->singleton(PublishOpsSitesMigrationsInstallStep::class, fn (): PublishOpsSitesMigrationsInstallStep => new PublishOpsSitesMigrationsInstallStep($this->app->make(OpsSitesStoreSetup::class)));
        $this->app->singleton(EnsureOpsSitesStoreReadyInstallStep::class, fn (): EnsureOpsSitesStoreReadyInstallStep => new EnsureOpsSitesStoreReadyInstallStep($this->app->make(OpsSitesStoreSetup::class)));
        $this->app->singleton(ConfigureOpsSitesAuditInstallStep::class);

        $this->app->singleton(SiteInventoryResolver::class);
        $this->app->singleton(DomainPostureResolver::class);
        $this->app->singleton(DnsPostureResolver::class);
        $this->app->singleton(SslAssignmentResolver::class);
        $this->app->singleton(SiteInfrastructureAssignmentResolver::class);

        $this->app->singleton(OpsSitesManager::class, function (): OpsSitesManager {
            return new OpsSitesManager(
                inventoryResolver: $this->app->make(SiteInventoryResolver::class),
                domainResolver: $this->app->make(DomainPostureResolver::class),
                dnsResolver: $this->app->make(DnsPostureResolver::class),
                sslResolver: $this->app->make(SslAssignmentResolver::class),
                assignmentResolver: $this->app->make(SiteInfrastructureAssignmentResolver::class),
                storeSetup: $this->app->make(OpsSitesStoreSetup::class),
                cacheFactory: $this->app->make(CacheFactory::class),
                cacheEnabled: (bool) config('ops-sites.cache.enabled', true),
                cacheStore: config('ops-sites.cache.store'),
                cacheTtl: (int) config('ops-sites.cache.ttl', 300),
            );
        });

        $this->app->singleton(SitesStoreReadyCheck::class, fn (): SitesStoreReadyCheck => new SitesStoreReadyCheck($this->app->make(OpsSitesStoreSetup::class)));
        $this->app->singleton(PrimaryDomainAssignedCheck::class, fn (): PrimaryDomainAssignedCheck => new PrimaryDomainAssignedCheck($this->app->make(OpsSitesManager::class)));
        $this->app->singleton(DnsTargetsResolvableCheck::class, fn (): DnsTargetsResolvableCheck => new DnsTargetsResolvableCheck($this->app->make(OpsSitesManager::class)));
        $this->app->singleton(SiteAssignmentsConfiguredCheck::class, fn (): SiteAssignmentsConfiguredCheck => new SiteAssignmentsConfiguredCheck($this->app->make(OpsSitesManager::class)));

        $this->app->singleton(RefreshSitesPostureAction::class, fn (): RefreshSitesPostureAction => new RefreshSitesPostureAction(
            manager: $this->app->make(OpsSitesManager::class),
            events: $this->app->make(Dispatcher::class),
        ));

        $this->app->singleton(MutateSiteAction::class, fn (): MutateSiteAction => new MutateSiteAction(
            manager: $this->app->make(OpsSitesManager::class),
            events: $this->app->make(Dispatcher::class),
        ));
    }

    public function packageBooted(): void
    {
        $this->app->make(PlatformPackageRegistrar::class)->register(new OpsSitesPlatformPackage);
        $this->registerAuditListeners($this->app->make(Dispatcher::class));
    }

    private function registerAuditListeners(Dispatcher $events): void
    {
        $events->listen(SitesPostureRefreshed::class, [OpsSitesAuditListener::class, 'handleSitesPostureRefreshed']);
        $events->listen(SiteMutated::class, [OpsSitesAuditListener::class, 'handleSiteMutated']);
    }

    private function makeAuditWriter(): OpsSitesAuditWriter
    {
        $driver = config('ops-sites.audit.driver');

        if ($driver === null) {
            return new NullOpsSitesAuditWriter;
        }

        if ($driver !== 'activitylog') {
            throw new InvalidArgumentException(sprintf('Unsupported ops sites audit driver [%s].', $driver));
        }

        if (! class_exists('Spatie\\Activitylog\\ActivitylogServiceProvider') || ! class_exists(ActivityLogger::class)) {
            throw new InvalidArgumentException('Ops sites audit driver [activitylog] requires spatie/laravel-activitylog.');
        }

        return new ActivityLogOpsSitesAuditWriter($this->app->make(ActivityLogger::class));
    }
}
