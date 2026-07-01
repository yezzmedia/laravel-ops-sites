<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Install;

use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\InstallStep;
use YezzMedia\Foundation\Install\OptionalInstallStep;
use YezzMedia\OpsSites\Support\OpsSitesStoreSetup;

final class EnsureOpsSitesStoreReadyInstallStep implements InstallStep, OptionalInstallStep
{
    public function __construct(private readonly OpsSitesStoreSetup $storeSetup) {}

    public function key(): string
    {
        return 'ensure_ops_sites_store_ready';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-ops-sites';
    }

    public function priority(): int
    {
        return 220;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return ! $this->storeSetup->storeReady();
    }

    public function handle(InstallContext $context): void
    {
        if ($this->storeSetup->hasPartialTables()) {
            fwrite(
                STDERR,
                "\n  \033[33;1mWARNING\033[39;22m  Ops sites store has a partial table set. Resolve the partial state before continuing.\n\n"
            );

            return;
        }

        if (! $context->allowMigrations) {
            fwrite(
                STDERR,
                "\n  \033[33;1mWARNING\033[39;22m  Ops sites store is not ready and migrations are disabled for this install run.\n\n"
            );

            return;
        }

        $this->storeSetup->runMigrations();

        if (! $this->storeSetup->storeReady()) {
            fwrite(
                STDERR,
                "\n  \033[33;1mWARNING\033[39;22m  Ops sites store is still not ready after running package migrations.\n\n"
            );
        }
    }

    public function isOptional(): bool
    {
        return true;
    }
}
