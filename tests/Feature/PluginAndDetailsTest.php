<?php

declare(strict_types=1);

use Filament\Panel;
use YezzMedia\OpsSites\Filament\OpsSitesFilamentPlugin;
use YezzMedia\OpsSites\Filament\Pages\OpsSitesPage;
use YezzMedia\OpsSites\Filament\Pages\SiteDetailsPage;

it('registers the ops sites plugin id', function (): void {
    expect(OpsSitesFilamentPlugin::make()->getId())->toBe('ops-sites');
});

it('registers ops sites pages on a panel', function (): void {
    $registeredPages = [];
    $panelMock = Mockery::mock(Panel::class);
    $panelMock->shouldReceive('pages')
        ->once()
        ->withArgs(function (array $pages) use (&$registeredPages): bool {
            $registeredPages = $pages;

            return true;
        })
        ->andReturnSelf();

    OpsSitesFilamentPlugin::make()->register($panelMock);

    expect($registeredPages)->toBe([
        OpsSitesPage::class,
        SiteDetailsPage::class,
    ]);
});
