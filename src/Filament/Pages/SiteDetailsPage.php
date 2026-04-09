<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;
use YezzMedia\OpsSites\Actions\MutateSiteAction;
use YezzMedia\OpsSites\Models\OpsSite;
use YezzMedia\OpsSites\Support\OpsSitesFormSchema;
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
        $site = OpsSite::query()
            ->with(['domains', 'assignments'])
            ->where('site_key', $this->site)
            ->first();

        return [
            Action::make('back')
                ->label('Back to Sites')
                ->icon('heroicon-o-arrow-left')
                ->url(OpsSitesPage::getUrl()),
            Action::make('editSite')
                ->label('Edit Site')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => Gate::check('ops.sites.manage') && $site !== null)
                ->schema(OpsSitesFormSchema::schema(includeSiteKey: false))
                ->fillForm(fn (): array => $site === null ? [] : OpsSitesFormSchema::dataFromSite($site))
                ->action(fn (array $data): mixed => $this->editSite($data)),
            Action::make('archiveSite')
                ->label('Archive Site')
                ->icon('heroicon-o-archive-box')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => Gate::check('ops.sites.manage') && $site !== null)
                ->action(fn (): mixed => $this->archiveSite()),
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
        $domainRecords = $manager->domainsFor($site->key);
        $dnsRecords = $manager->dnsPostureFor($site->key);
        $sslRecords = $manager->sslAssignmentsFor($site->key);

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
                ->schema($domainRecords === []
                    ? [Text::make('No domain mappings are currently configured.')->color('gray')]
                    : array_merge(...array_map(
                        fn ($record): array => [Text::make($record->domain)->badge()->color($record->status->color()), Text::make($record->message)->color($record->status->color())],
                        $domainRecords,
                    ))),
            Section::make('DNS Posture')
                ->schema($dnsRecords === []
                    ? [Text::make('No DNS posture is currently configured.')->color('gray')]
                    : array_merge(...array_map(
                        fn ($record): array => [Text::make($record->domain)->badge()->color($record->status->color()), Text::make($record->message)->color($record->status->color())],
                        $dnsRecords,
                    ))),
            Section::make('SSL Assignment Visibility')
                ->schema($sslRecords === []
                    ? [Text::make('No SSL assignment data is currently configured.')->color('gray')]
                    : array_merge(...array_map(
                        fn ($record): array => [Text::make($record->domain)->badge()->color('gray'), Text::make($record->message)->color('gray')],
                        $sslRecords,
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

    /**
     * @param  array<string, mixed>  $data
     */
    public function editSite(array $data): mixed
    {
        $site = OpsSite::query()
            ->where('site_key', $this->site)
            ->firstOrFail();

        app(MutateSiteAction::class)->update($site, $data, 'filament');

        Notification::make()
            ->success()
            ->title('Site updated')
            ->body(sprintf('Updated [%s].', $site->getAttribute('name')))
            ->send();

        return $this->redirect(static::getUrl(['site' => $this->site]));
    }

    public function archiveSite(): mixed
    {
        $site = OpsSite::query()
            ->where('site_key', $this->site)
            ->firstOrFail();

        app(MutateSiteAction::class)->archive($site, 'filament');

        Notification::make()
            ->success()
            ->title('Site archived')
            ->body(sprintf('Archived [%s].', $site->getAttribute('name')))
            ->send();

        return $this->redirect(OpsSitesPage::getUrl());
    }
}
