<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use YezzMedia\OpsSites\Data\DnsPostureRecord;
use YezzMedia\OpsSites\Data\DomainPostureRecord;
use YezzMedia\OpsSites\Data\OpsSiteRecord;
use YezzMedia\OpsSites\Data\OpsSiteSummary;
use YezzMedia\OpsSites\Data\SiteInfrastructureAssignmentRecord;
use YezzMedia\OpsSites\Data\SiteLifecycleSummary;
use YezzMedia\OpsSites\Data\SslAssignmentRecord;

final class OpsSitesManager
{
    private CacheRepository $cache;

    private ?OpsSiteSummary $summaryMemo = null;

    public function __construct(
        private readonly SiteInventoryResolver $inventoryResolver,
        private readonly DomainPostureResolver $domainResolver,
        private readonly DnsPostureResolver $dnsResolver,
        private readonly SslAssignmentResolver $sslResolver,
        private readonly SiteInfrastructureAssignmentResolver $assignmentResolver,
        CacheFactory $cacheFactory,
        private readonly bool $cacheEnabled,
        private readonly ?string $cacheStore,
        private readonly int $cacheTtl,
    ) {
        $this->cache = $cacheFactory->store($cacheStore);
    }

    public function summary(): OpsSiteSummary
    {
        if ($this->summaryMemo instanceof OpsSiteSummary) {
            return $this->summaryMemo;
        }

        if ($this->cacheEnabled) {
            /** @var OpsSiteSummary|null $cached */
            $cached = $this->cache->get($this->cacheKey());

            if ($cached instanceof OpsSiteSummary) {
                return $this->summaryMemo = $cached;
            }
        }

        $summary = $this->computeSummary();

        if ($this->cacheEnabled) {
            $this->cache->put($this->cacheKey(), $summary, $this->cacheTtl);
        }

        return $this->summaryMemo = $summary;
    }

    /**
     * @return array<int, OpsSiteRecord>
     */
    public function sites(): array
    {
        return $this->summary()->sites;
    }

    public function site(string $siteKey): ?OpsSiteRecord
    {
        foreach ($this->sites() as $site) {
            if ($site->key === $siteKey) {
                return $site;
            }
        }

        return null;
    }

    /**
     * @return array<int, DomainPostureRecord>
     */
    public function domainsFor(string $siteKey): array
    {
        return $this->domainResolver->resolveForSite($siteKey);
    }

    /**
     * @return array<int, DnsPostureRecord>
     */
    public function dnsPostureFor(string $siteKey): array
    {
        return $this->dnsResolver->resolveForSite($siteKey);
    }

    /**
     * @return array<int, SslAssignmentRecord>
     */
    public function sslAssignmentsFor(string $siteKey): array
    {
        return $this->sslResolver->resolveForSite($siteKey);
    }

    public function assignmentFor(string $siteKey): ?SiteInfrastructureAssignmentRecord
    {
        return $this->assignmentResolver->resolveForSite($siteKey);
    }

    public function lifecycleSummaryFor(string $siteKey): ?SiteLifecycleSummary
    {
        $site = $this->site($siteKey);

        if ($site === null) {
            return null;
        }

        return new SiteLifecycleSummary(
            status: $site->lifecycleStatus,
            message: sprintf('%s is currently %s.', $site->name, strtolower($site->lifecycleStatus->label())),
        );
    }

    public function overallStatus(): DomainPostureStatus
    {
        return $this->summary()->overallStatus;
    }

    public function refresh(): OpsSiteSummary
    {
        $this->summaryMemo = null;
        $this->cache->forget($this->cacheKey());

        return $this->summary();
    }

    private function computeSummary(): OpsSiteSummary
    {
        $sites = $this->inventoryResolver->resolve();
        $statuses = array_map(fn (OpsSiteRecord $site): DomainPostureStatus => $site->domainStatus, $sites);
        $overallStatus = DomainPostureStatus::worst($statuses === [] ? [DomainPostureStatus::Unsupported] : $statuses);
        $warningDomains = [];

        foreach ($sites as $site) {
            foreach ($this->domainsFor($site->key) as $domain) {
                if ($domain->status !== DomainPostureStatus::Healthy) {
                    $warningDomains[] = $domain;
                }
            }
        }

        return new OpsSiteSummary(
            overallStatus: $overallStatus,
            siteCount: count($sites),
            healthyCount: count(array_filter($statuses, fn (DomainPostureStatus $status): bool => $status === DomainPostureStatus::Healthy)),
            warningCount: count(array_filter($statuses, fn (DomainPostureStatus $status): bool => $status === DomainPostureStatus::Warning)),
            driftedCount: count(array_filter($statuses, fn (DomainPostureStatus $status): bool => $status === DomainPostureStatus::Drifted)),
            failingCount: count(array_filter($statuses, fn (DomainPostureStatus $status): bool => $status === DomainPostureStatus::Failed)),
            completedAt: CarbonImmutable::now(),
            sites: $sites,
            warningDomains: $warningDomains,
        );
    }

    private function cacheKey(): string
    {
        return 'ops_sites.summary';
    }
}
