<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use UnitEnum;
use YezzMedia\OpsSites\Actions\MutateSiteAction;
use YezzMedia\OpsSites\Actions\RefreshSitesPostureAction;
use YezzMedia\OpsSites\Support\DomainPostureStatus;
use YezzMedia\OpsSites\Support\OpsSitesFormSchema;
use YezzMedia\OpsSites\Support\OpsSitesManager;

class OpsSitesPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|UnitEnum|null $navigationGroup = 'Sites';

    protected static ?string $navigationLabel = 'Sites';

    protected static ?int $navigationSort = 70;

    protected static ?string $title = 'Sites Posture';

    protected static ?string $slug = 'ops-sites';

    public static function canAccess(): bool
    {
        return Gate::check('ops.sites.view');
    }

    public static function getNavigationBadge(): ?string
    {
        return app(OpsSitesManager::class)->overallStatus()->label();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return app(OpsSitesManager::class)->overallStatus()->color();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSite')
                ->label('Create Site')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => Gate::check('ops.sites.manage'))
                ->schema(OpsSitesFormSchema::schema())
                ->action(fn (array $data): mixed => $this->createSite($data)),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $summary = app(OpsSitesManager::class)->summary();

        return $schema->components([
            $this->overviewSection($summary),
            $this->inventorySection($summary),
            $this->warningsSection($summary),
            $this->actionsSection(),
        ]);
    }

    private function overviewSection($summary): Section
    {
        return Section::make('Overview')
            ->schema([
                Grid::make(5)->schema([
                    ...$this->labeledText('Overall Status', $summary->overallStatus->label(), color: $summary->overallStatus->color(), icon: $summary->overallStatus->icon(), badge: true),
                    ...$this->labeledText('Sites', (string) $summary->siteCount, color: 'gray', badge: true),
                    ...$this->labeledText('Healthy', (string) $summary->healthyCount, color: 'success', badge: true),
                    ...$this->labeledText('Warnings', (string) $summary->warningCount, color: $summary->warningCount > 0 ? 'warning' : 'gray', badge: true),
                    ...$this->labeledText('Drift / Failures', (string) ($summary->driftedCount + $summary->failingCount), color: ($summary->driftedCount + $summary->failingCount) > 0 ? 'danger' : 'gray', badge: true),
                ]),
                ...$this->labeledText('Last checked', $summary->completedAt->format('Y-m-d H:i:s T'), color: 'gray'),
            ]);
    }

    private function inventorySection($summary): Section
    {
        if ($summary->sites === []) {
            return Section::make('Site Inventory')
                ->schema([
                    Text::make('No sites are currently registered.')
                        ->color('gray'),
                ]);
        }

        return Section::make('Site Inventory')
            ->schema(
                array_merge(...array_map(function ($site): array {
                    $statusColor = $site->domainStatus instanceof DomainPostureStatus ? $site->domainStatus->color() : 'gray';

                    return [
                        ...$this->labeledText($site->name, sprintf('%s | %s | %s', $site->primaryDomain ?? 'No primary domain', $site->assignmentTarget, $site->lifecycleStatus->label()), color: $statusColor, icon: $site->domainStatus->icon()),
                    ];
                }, $summary->sites)),
            );
    }

    private function warningsSection($summary): Section
    {
        if ($summary->warningDomains === []) {
            return Section::make('Warnings and Drift')
                ->schema([
                    Text::make('No site-domain drift or warnings are currently tracked.')
                        ->color('success'),
                ]);
        }

        return Section::make('Warnings and Drift')
            ->schema(
                array_merge(...array_map(
                    fn ($record): array => $this->labeledText($record->domain, $record->message, color: $record->status->color(), icon: $record->status->icon()),
                    $summary->warningDomains,
                )),
            );
    }

    /**
     * @return array{Text, Text}
     */
    private function labeledText(
        string $label,
        string $value,
        ?string $color = null,
        ?string $icon = null,
        bool $badge = false,
    ): array {
        $valueText = Text::make($value);

        if ($badge) {
            $valueText = $valueText->badge();
        }

        if ($color !== null) {
            $valueText = $valueText->color($color);
        }

        if ($icon !== null) {
            $valueText = $valueText->icon($icon);
        }

        return [
            Text::make($label)
                ->badge()
                ->color('gray'),
            $valueText,
        ];
    }

    private function actionsSection(): Actions
    {
        return Actions::make([
            Action::make('refresh')
                ->label('Refresh Sites Posture')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Refresh Sites Posture')
                ->modalDescription('This will rebuild the current site, domain, DNS, SSL assignment, and infrastructure assignment visibility snapshot.')
                ->visible(fn (): bool => Gate::check('ops.sites.manage'))
                ->action(function (): void {
                    app(RefreshSitesPostureAction::class)->execute('filament');

                    Notification::make()
                        ->success()
                        ->title('Sites posture refreshed')
                        ->send();
                }),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSite(array $data): mixed
    {
        $site = app(MutateSiteAction::class)->create($data, 'filament');

        Notification::make()
            ->success()
            ->title('Site created')
            ->body(sprintf('Created [%s].', $site->getAttribute('name')))
            ->send();

        return $this->redirect(SiteDetailsPage::getUrl(['site' => $site->getAttribute('site_key')]));
    }
}
