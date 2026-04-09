<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Install;

use RuntimeException;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\AuditInstallStep;
use YezzMedia\Foundation\Install\OptionalInstallStep;

final class ConfigureOpsSitesAuditInstallStep implements AuditInstallStep, OptionalInstallStep
{
    public function key(): string
    {
        return 'configure_ops_sites_audit';
    }

    public function package(): string
    {
        return 'yezzmedia/laravel-ops-sites';
    }

    public function priority(): int
    {
        return 230;
    }

    public function shouldRun(InstallContext $context): bool
    {
        return $context->shouldConfigureAuditFor('yezzmedia/laravel-ops-sites');
    }

    public function handle(InstallContext $context): void
    {
        $configPath = config_path('ops-sites.php');

        if (! is_file($configPath)) {
            throw new RuntimeException('Unable to configure ops sites audit because config/ops-sites.php is missing.');
        }

        $contents = file_get_contents($configPath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read config/ops-sites.php while configuring ops sites audit.');
        }

        $updated = str_replace("'driver' => env('OPS_SITES_AUDIT_DRIVER'),", "'driver' => env('OPS_SITES_AUDIT_DRIVER', 'activitylog'),", $contents, $count);

        if ($count === 0) {
            throw new RuntimeException('Unable to locate ops sites audit driver configuration while configuring audit support.');
        }

        file_put_contents($configPath, $updated);
    }

    public function isOptional(): bool
    {
        return true;
    }
}
