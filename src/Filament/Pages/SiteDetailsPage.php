<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;
use YezzMedia\OpsSites\Support\OpsSitesManager;

class SiteDetailsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $slug = 'ops-sites/detail';

    protected static string|UnitEnum|null $navigationGroup = 'Sites';

    #[Url]
    public string $site = '';

    public static function canAccess(): bool
    {
        return Gate::check('ops.sites.view');
    }

    public function getTitle(): string
    {
        $site = app(OpsSitesManager::class)->site($this->site);

        return $site === null ? 'Site Detail' : sprintf('Site Detail: %s', $site->name);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Sites')
                ->icon('heroicon-o-arrow-left')
                ->url(OpsSitesPage::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $manager = app(OpsSitesManager::class);
        $site = $manager->site($this->site);

        if ($site === null) {
            return $schema->components([
                Section::make('Site Summary')
                    ->schema([
                        Text::make('The requested site could not be found.')
                            ->color('danger'),
                    ]),
            ]);
        }

        $assignment = $manager->assignmentFor($site->key);
        $lifecycle = $manager->lifecycleSummaryFor($site->key);

        return $schema->components([
            Section::make('Site Summary')
                ->schema([
                    Grid::make(3)->schema([
                        Text::make($site->name)->badge()->color('primary'),
                        Text::make($site->primaryDomain ?? 'No primary domain')->badge()->color('gray'),
                        Text::make($site->lifecycleStatus->label())->badge()->color('gray'),
                    ]),
                ]),
            Section::make('Domains')
                ->schema(array_merge(...array_map(
                    fn ($record): array => [Text::make($record->domain)->badge()->color($record->status->color()), Text::make($record->message)->color($record->status->color())],
                    $manager->domainsFor($site->key),
                ))),
            Section::make('DNS Posture')
                ->schema(array_merge(...array_map(
                    fn ($record): array => [Text::make($record->domain)->badge()->color($record->status->color()), Text::make($record->message)->color($record->status->color())],
                    $manager->dnsPostureFor($site->key),
                ))),
            Section::make('SSL Assignment Visibility')
                ->schema(array_merge(...array_map(
                    fn ($record): array => [Text::make($record->domain)->badge()->color('gray'), Text::make($record->message)->color('gray')],
                    $manager->sslAssignmentsFor($site->key),
                ))),
            Section::make('Infrastructure Assignment')
                ->schema([
                    Text::make($assignment?->target ?? 'Unassigned')->badge()->color('gray'),
                    Text::make($assignment?->message ?? 'No infrastructure assignment is currently configured.')->color('gray'),
                ]),
            Section::make('Lifecycle Notes')
                ->schema([
                    Text::make($lifecycle?->message ?? 'No lifecycle data available.')->color('gray'),
                ]),
        ]);
    }
}
