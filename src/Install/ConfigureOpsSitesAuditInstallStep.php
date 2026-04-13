<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Install;

use RuntimeException;
use YezzMedia\Foundation\Data\InstallContext;
use YezzMedia\Foundation\Install\AuditInstallStep;
use YezzMedia\Foundation\Install\OptionalInstallStep;

final class ConfigureOpsSitesAuditInstallStep implements AuditInstallStep, OptionalInstallStep
{
    private const DRIVER_WITHOUT_DEFAULT = "'driver' => env('OPS_SITES_AUDIT_DRIVER'),";

    private const DRIVER_WITH_ACTIVITYLOG_DEFAULT = "'driver' => env('OPS_SITES_AUDIT_DRIVER', 'activitylog'),";

    private const PACKAGE_CONFIG_PATH = __DIR__.'/../../config/ops-sites.php';

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
            $this->publishConfig($configPath);
        }

        $contents = file_get_contents($configPath);

        if ($contents === false) {
            throw new RuntimeException('Unable to read config/ops-sites.php while configuring ops sites audit.');
        }

        if (str_contains($contents, self::DRIVER_WITH_ACTIVITYLOG_DEFAULT)) {
            return;
        }

        $updated = str_replace(self::DRIVER_WITHOUT_DEFAULT, self::DRIVER_WITH_ACTIVITYLOG_DEFAULT, $contents, $count);

        if ($count === 0) {
            throw new RuntimeException('Unable to locate ops sites audit driver configuration while configuring audit support.');
        }

        if (file_put_contents($configPath, $updated) === false) {
            throw new RuntimeException('Unable to write config/ops-sites.php while configuring ops sites audit.');
        }
    }

    private function publishConfig(string $path): void
    {
        if (! is_file(self::PACKAGE_CONFIG_PATH) || ! is_readable(self::PACKAGE_CONFIG_PATH)) {
            throw new RuntimeException('Unable to read the ops sites package config while configuring audit support.');
        }

        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the config directory for ops sites audit configuration.');
        }

        $contents = file_get_contents(self::PACKAGE_CONFIG_PATH);

        if ($contents === false) {
            throw new RuntimeException('Unable to load the ops sites package config while configuring audit support.');
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to publish config/ops-sites.php while configuring ops sites audit.');
        }
    }

    public function isOptional(): bool
    {
        return true;
    }
}
