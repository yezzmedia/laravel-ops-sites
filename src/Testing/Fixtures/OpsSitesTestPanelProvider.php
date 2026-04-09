<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Testing\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use YezzMedia\OpsSites\Filament\OpsSitesFilamentPlugin;

final class OpsSitesTestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('ops-sites-test')
            ->path('ops-sites-test')
            ->authGuard('web')
            ->plugin(OpsSitesFilamentPlugin::make());
    }
}
