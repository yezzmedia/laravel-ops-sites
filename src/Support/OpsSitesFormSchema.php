<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use YezzMedia\OpsSites\Models\OpsSite;

final class OpsSitesFormSchema
{
    /**
     * @return array<int, mixed>
     */
    public static function schema(bool $includeSiteKey = true): array
    {
        $schema = [];

        if ($includeSiteKey) {
            $schema[] = TextInput::make('site_key')
                ->label('Site Key')
                ->required()
                ->maxLength(255)
                ->helperText('Stable lowercase key used for lookups and detail routes.');
        }

        $schema[] = TextInput::make('name')
            ->label('Site Name')
            ->required()
            ->maxLength(255);

        $schema[] = Select::make('lifecycle_status')
            ->label('Lifecycle Status')
            ->options(self::lifecycleOptions())
            ->required();

        $schema[] = Repeater::make('domains')
            ->label('Domains')
            ->schema([
                TextInput::make('domain')
                    ->label('Domain')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_primary')
                    ->label('Primary domain')
                    ->default(false),
                Textarea::make('expected_dns_targets')
                    ->label('Expected DNS Targets')
                    ->rows(3)
                    ->helperText('One target per line or comma separated.'),
                Textarea::make('resolved_dns_targets')
                    ->label('Resolved DNS Targets')
                    ->rows(3)
                    ->helperText('One target per line or comma separated.'),
                TextInput::make('certificate_reference')
                    ->label('Certificate Reference')
                    ->maxLength(255),
            ])
            ->defaultItems(1)
            ->reorderable(false)
            ->required();

        $schema[] = TextInput::make('assignment_target')
            ->label('Infrastructure Target')
            ->maxLength(255)
            ->helperText('Example: cluster-a, edge-eu, or another deployment target.');

        $schema[] = Textarea::make('metadata_json')
            ->label('Site Metadata JSON')
            ->rows(4)
            ->helperText('Optional JSON object or array stored on the site record.');

        $schema[] = Textarea::make('assignment_metadata_json')
            ->label('Assignment Metadata JSON')
            ->rows(4)
            ->helperText('Optional JSON object or array stored on the assignment record.');

        return $schema;
    }

    /**
     * @return array<string, string>
     */
    public static function dataFromSite(OpsSite $site): array
    {
        $assignment = $site->assignments->sortBy('id')->first();

        return [
            'site_key' => (string) $site->getAttribute('site_key'),
            'name' => (string) $site->getAttribute('name'),
            'lifecycle_status' => (string) $site->getAttribute('lifecycle_status'),
            'domains' => $site->domains
                ->sortByDesc('is_primary')
                ->sortBy('domain')
                ->values()
                ->map(fn (mixed $domain): array => [
                    'domain' => (string) $domain->getAttribute('domain'),
                    'is_primary' => (bool) $domain->getAttribute('is_primary'),
                    'expected_dns_targets' => implode(PHP_EOL, array_values((array) $domain->getAttribute('expected_dns_targets'))),
                    'resolved_dns_targets' => implode(PHP_EOL, array_values((array) $domain->getAttribute('resolved_dns_targets'))),
                    'certificate_reference' => (string) ($domain->getAttribute('certificate_reference') ?? ''),
                ])
                ->all(),
            'assignment_target' => (string) ($assignment?->getAttribute('target_reference') ?? ''),
            'metadata_json' => $site->getAttribute('metadata') === null
                ? ''
                : json_encode($site->getAttribute('metadata'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'assignment_metadata_json' => $assignment?->getAttribute('metadata') === null
                ? ''
                : json_encode($assignment->getAttribute('metadata'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function lifecycleOptions(): array
    {
        $options = [];

        foreach (SiteLifecycleStatus::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
