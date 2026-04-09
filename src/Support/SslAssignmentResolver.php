<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use YezzMedia\OpsSites\Data\SslAssignmentRecord;
use YezzMedia\OpsSites\Models\OpsSiteDomain;

final class SslAssignmentResolver
{
    /**
     * @return array<int, SslAssignmentRecord>
     */
    public function resolveForSite(string $siteKey): array
    {
        return OpsSiteDomain::query()
            ->whereHas('site', fn ($query) => $query->where('site_key', $siteKey))
            ->orderByDesc('is_primary')
            ->orderBy('domain')
            ->get()
            ->map(fn (OpsSiteDomain $domain): SslAssignmentRecord => new SslAssignmentRecord(
                domain: (string) $domain->getAttribute('domain'),
                status: SslAssignmentStatus::tryFrom((string) $domain->getAttribute('ssl_assignment_status')) ?? SslAssignmentStatus::Unknown,
                certificateReference: $domain->getAttribute('certificate_reference'),
                message: (string) $domain->getAttribute('ssl_assignment_message'),
            ))
            ->values()
            ->all();
    }
}
