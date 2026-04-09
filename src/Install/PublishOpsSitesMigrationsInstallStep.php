<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Install;

use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\InstallStep;
use YezzMedia\OpsSites\Support\OpsSitesStoreSetup;

final class PublishOpsSitesMigrationsInstallStep implements InstallStep
{
    public function __construct(private readonly OpsSitesStoreSetup $storeSetup) {}

    public function key(): string
    {
        return 'publish_ops_sites_migrations';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-ops-sites';
    }

    public function priority(): int
    {
        return 210;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return ! $this->storeSetup->storeReady();
    }

    public function handle(InstallContext $context): void
    {
        // Package migrations are registered through package-tools.
    }
}
