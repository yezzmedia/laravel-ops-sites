<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use YezzMedia\OpsSites\Filament\Pages\OpsSitesPage;
use YezzMedia\OpsSites\Filament\Pages\SiteDetailsPage;

final class OpsSitesFilamentPlugin implements Plugin
{
    public static function make(): static
    {
        return app(self::class);
    }

    public function getId(): string
    {
        return 'ops-sites';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            OpsSitesPage::class,
            SiteDetailsPage::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
