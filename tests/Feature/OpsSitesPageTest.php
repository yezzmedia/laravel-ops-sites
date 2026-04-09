<?php

declare(strict_types=1);

use Filament\Schemas\Components\Actions as ActionsComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use YezzMedia\OpsSites\Doctor\DnsTargetsResolvableCheck;
use YezzMedia\OpsSites\Doctor\PrimaryDomainAssignedCheck;
use YezzMedia\OpsSites\Doctor\SiteAssignmentsConfiguredCheck;
use YezzMedia\OpsSites\Filament\Pages\OpsSitesPage;
use YezzMedia\OpsSites\Filament\Pages\SiteDetailsPage;
use YezzMedia\OpsSites\Models\OpsSite;
use YezzMedia\OpsSites\Models\OpsSiteAssignment;
use YezzMedia\OpsSites\Models\OpsSiteDomain;
use YezzMedia\OpsSites\Support\OpsSitesManager;

beforeEach(function (): void {
    $site = OpsSite::query()->create([
        'site_key' => 'alpha',
        'name' => 'Alpha Site',
        'lifecycle_status' => 'active',
    ]);

    OpsSiteDomain::query()->create([
        'site_id' => $site->getKey(),
        'domain' => 'alpha.example.com',
        'is_primary' => true,
        'domain_status' => 'warning',
        'domain_message' => 'Primary domain is close to drift.',
        'dns_status' => 'drifted',
        'dns_message' => 'DNS target does not match the expected edge.',
        'expected_dns_targets' => ['198.51.100.10'],
        'resolved_dns_targets' => ['203.0.113.20'],
        'ssl_assignment_status' => 'assigned',
        'certificate_reference' => 'cert-alpha',
        'ssl_assignment_message' => 'Certificate reference is assigned.',
    ]);

    OpsSiteAssignment::query()->create([
        'site_id' => $site->getKey(),
        'assignment_status' => 'assigned',
        'target_reference' => 'cluster-a',
        'assignment_message' => 'Assigned to cluster-a.',
    ]);
});

it('builds the ops sites page schema', function (): void {
    Gate::define('ops.sites.view', fn (): bool => true);
    Gate::define('ops.sites.manage', fn (): bool => true);

    $page = app(OpsSitesPage::class);
    $schema = $page->content(Schema::make($page));
    $components = $schema->getComponents(withActions: false, withHidden: true);

    expect($components)->toHaveCount(4)
        ->and($components[0])->toBeInstanceOf(Section::class)
        ->and($components[0]->getHeading())->toBe('Overview')
        ->and($components[1])->toBeInstanceOf(Section::class)
        ->and($components[1]->getHeading())->toBe('Site Inventory')
        ->and($components[3])->toBeInstanceOf(ActionsComponent::class);
});

it('returns the expected site summary and detail records', function (): void {
    $manager = app(OpsSitesManager::class);
    $summary = $manager->summary();
    $site = $manager->site('alpha');

    expect($summary->siteCount)->toBe(1)
        ->and($summary->warningCount)->toBe(1)
        ->and($summary->driftedCount)->toBe(0)
        ->and($summary->failingCount)->toBe(0)
        ->and($summary->warningDomains)->toHaveCount(1)
        ->and($site)->not->toBeNull()
        ->and($site?->name)->toBe('Alpha Site')
        ->and($site?->primaryDomain)->toBe('alpha.example.com')
        ->and($manager->domainsFor('alpha'))->toHaveCount(1)
        ->and($manager->dnsPostureFor('alpha')[0]->resolvedTargets)->toBe(['203.0.113.20'])
        ->and($manager->sslAssignmentsFor('alpha')[0]->certificateReference)->toBe('cert-alpha')
        ->and($manager->assignmentFor('alpha')?->target)->toBe('cluster-a')
        ->and($manager->lifecycleSummaryFor('alpha')?->message)->toContain('Alpha Site');
});

it('builds the site details page schema for a tracked site', function (): void {
    Gate::define('ops.sites.view', fn (): bool => true);

    $page = app(SiteDetailsPage::class);
    $page->site = 'alpha';

    $schema = $page->content(Schema::make($page));
    $components = $schema->getComponents();

    expect($page->getTitle())->toBe('Site Detail: Alpha Site')
        ->and($components)->toHaveCount(6)
        ->and($components[0]->getHeading())->toBe('Site Summary')
        ->and($components[5]->getHeading())->toBe('Lifecycle Notes');
});

it('shows a fallback detail message for an unknown site', function (): void {
    Gate::define('ops.sites.view', fn (): bool => true);

    $page = app(SiteDetailsPage::class);
    $page->site = 'missing-site';

    $schema = $page->content(Schema::make($page));
    $components = $schema->getComponents();

    expect($page->getTitle())->toBe('Site Detail')
        ->and($components)->toHaveCount(1)
        ->and($components[0]->getHeading())->toBe('Site Summary');
});

it('reports doctor results from the seeded site state', function (): void {
    $primaryDomainCheck = app(PrimaryDomainAssignedCheck::class)->run();
    $dnsCheck = app(DnsTargetsResolvableCheck::class)->run();
    $assignmentCheck = app(SiteAssignmentsConfiguredCheck::class)->run();

    expect($primaryDomainCheck->status)->toBe('passed')
        ->and($dnsCheck->status)->toBe('warning')
        ->and($dnsCheck->context['domains'])->toBe(['alpha.example.com'])
        ->and($assignmentCheck->status)->toBe('passed');
});
