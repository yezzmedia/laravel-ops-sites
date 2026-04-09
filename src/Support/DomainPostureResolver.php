<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use YezzMedia\OpsSites\Data\DomainPostureRecord;
use YezzMedia\OpsSites\Models\OpsSiteDomain;

final class DomainPostureResolver
{
    /**
     * @return array<int, DomainPostureRecord>
     */
    public function resolveForSite(string $siteKey): array
    {
        return OpsSiteDomain::query()
            ->whereHas('site', fn ($query) => $query->where('site_key', $siteKey))
            ->orderByDesc('is_primary')
            ->orderBy('domain')
            ->get()
            ->map(fn (OpsSiteDomain $domain): DomainPostureRecord => new DomainPostureRecord(
                domain: (string) $domain->getAttribute('domain'),
                status: DomainPostureStatus::tryFrom((string) $domain->getAttribute('domain_status')) ?? DomainPostureStatus::Unsupported,
                message: (string) $domain->getAttribute('domain_message'),
                isPrimary: (bool) $domain->getAttribute('is_primary'),
            ))
            ->values()
            ->all();
    }
}
