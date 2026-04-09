<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use YezzMedia\OpsSites\Data\DnsPostureRecord;
use YezzMedia\OpsSites\Models\OpsSiteDomain;

final class DnsPostureResolver
{
    /**
     * @return array<int, DnsPostureRecord>
     */
    public function resolveForSite(string $siteKey): array
    {
        return OpsSiteDomain::query()
            ->whereHas('site', fn ($query) => $query->where('site_key', $siteKey))
            ->orderByDesc('is_primary')
            ->orderBy('domain')
            ->get()
            ->map(fn (OpsSiteDomain $domain): DnsPostureRecord => new DnsPostureRecord(
                domain: (string) $domain->getAttribute('domain'),
                status: DomainPostureStatus::tryFrom((string) $domain->getAttribute('dns_status')) ?? DomainPostureStatus::Unsupported,
                expectedTargets: array_values((array) $domain->getAttribute('expected_dns_targets')),
                resolvedTargets: array_values((array) $domain->getAttribute('resolved_dns_targets')),
                message: (string) $domain->getAttribute('dns_message'),
            ))
            ->values()
            ->all();
    }
}
