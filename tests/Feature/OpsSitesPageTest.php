<?php

declare(strict_types=1);

use Filament\Schemas\Components\Actions as ActionsComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Validation\ValidationException;
use YezzMedia\OpsSites\Actions\MutateSiteAction;
use YezzMedia\OpsSites\Doctor\DnsTargetsResolvableCheck;
use YezzMedia\OpsSites\Doctor\PrimaryDomainAssignedCheck;
use YezzMedia\OpsSites\Doctor\SiteAssignmentsConfiguredCheck;
use YezzMedia\OpsSites\Filament\Pages\OpsSitesPage;
use YezzMedia\OpsSites\Filament\Pages\SiteDetailsPage;
use YezzMedia\OpsSites\Models\OpsSite;
use YezzMedia\OpsSites\Models\OpsSiteAssignment;
use YezzMedia\OpsSites\Models\OpsSiteDomain;
use YezzMedia\OpsSites\Support\DomainPostureStatus;
use YezzMedia\OpsSites\Support\OpsSitesManager;
use YezzMedia\OpsSites\Testing\Fixtures\TestOpsSitesUser;

beforeEach(function (): void {
    auth()->guard('web')->login(TestOpsSitesUser::fixture([
        'ops.sites.view',
        'ops.sites.manage',
    ]));

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

it('degrades safely when the ops sites store is not installed', function (): void {
    SchemaFacade::drop('ops_site_assignments');
    SchemaFacade::drop('ops_site_domains');
    SchemaFacade::drop('ops_sites');

    $manager = app(OpsSitesManager::class);
    $page = app(OpsSitesPage::class);

    expect($manager->summary()->overallStatus)->toBe(DomainPostureStatus::Unsupported)
        ->and($manager->summary()->siteCount)->toBe(0)
        ->and($page::getNavigationBadge())->toBe('Unsupported')
        ->and($page::getNavigationBadgeColor())->toBe('gray');
});

it('builds the site details page schema for a tracked site', function (): void {
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

it('creates a site from the ops sites page action', function (): void {
    $page = app(OpsSitesPage::class);

    $page->createSite([
        'site_key' => 'beta',
        'name' => 'Beta Site',
        'lifecycle_status' => 'provisioning',
        'domains' => [
            [
                'domain' => 'beta.example.com',
                'is_primary' => true,
                'expected_dns_targets' => "198.51.100.20\n198.51.100.21",
                'resolved_dns_targets' => "198.51.100.20\n198.51.100.21",
                'certificate_reference' => 'cert-beta',
            ],
        ],
        'assignment_target' => 'cluster-b',
        'metadata_json' => '{"tier":"gold"}',
        'assignment_metadata_json' => '{"region":"eu"}',
    ]);

    $site = OpsSite::query()->where('site_key', 'beta')->first();

    expect($site)->not->toBeNull()
        ->and($site?->getAttribute('name'))->toBe('Beta Site')
        ->and($site?->domains)->toHaveCount(1)
        ->and($site?->domains->first()?->getAttribute('dns_status'))->toBe('healthy')
        ->and($site?->assignments->first()?->getAttribute('target_reference'))->toBe('cluster-b');
});

it('updates a site from the detail page action', function (): void {
    $page = app(SiteDetailsPage::class);
    $page->site = 'alpha';

    $page->editSite([
        'name' => 'Alpha Site Updated',
        'lifecycle_status' => 'inactive',
        'domains' => [
            [
                'domain' => 'alpha.example.com',
                'is_primary' => false,
                'expected_dns_targets' => '198.51.100.10',
                'resolved_dns_targets' => '203.0.113.20',
                'certificate_reference' => '',
            ],
            [
                'domain' => 'www.alpha.example.com',
                'is_primary' => true,
                'expected_dns_targets' => '203.0.113.10',
                'resolved_dns_targets' => '203.0.113.10',
                'certificate_reference' => 'cert-www-alpha',
            ],
        ],
        'assignment_target' => 'cluster-z',
        'metadata_json' => '{"team":"platform"}',
        'assignment_metadata_json' => '{"managed":true}',
    ]);

    $site = OpsSite::query()->with(['domains', 'assignments'])->where('site_key', 'alpha')->first();

    expect($site)->not->toBeNull()
        ->and($site?->getAttribute('name'))->toBe('Alpha Site Updated')
        ->and($site?->getAttribute('lifecycle_status'))->toBe('inactive')
        ->and($site?->domains)->toHaveCount(2)
        ->and($site?->domains->firstWhere('is_primary', true)?->getAttribute('domain'))->toBe('www.alpha.example.com')
        ->and($site?->assignments->first()?->getAttribute('target_reference'))->toBe('cluster-z');
});

it('archives a site from the detail page action', function (): void {
    $page = app(SiteDetailsPage::class);
    $page->site = 'alpha';

    $page->archiveSite();

    expect(OpsSite::query()->where('site_key', 'alpha')->value('lifecycle_status'))->toBe('archived');
});

it('validates primary domain and unique domain rules in the mutation action', function (): void {
    expect(fn (): OpsSite => app(MutateSiteAction::class)->create([
        'site_key' => 'gamma',
        'name' => 'Gamma Site',
        'lifecycle_status' => 'active',
        'domains' => [
            [
                'domain' => 'gamma.example.com',
                'is_primary' => true,
            ],
            [
                'domain' => 'gamma.example.com',
                'is_primary' => true,
            ],
        ],
    ]))->toThrow(ValidationException::class);
});
