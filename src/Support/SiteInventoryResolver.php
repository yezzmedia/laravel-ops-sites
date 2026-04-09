<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use YezzMedia\OpsSites\Data\OpsSiteRecord;
use YezzMedia\OpsSites\Models\OpsSite;

final class SiteInventoryResolver
{
    /**
     * @return array<int, OpsSiteRecord>
     */
    public function resolve(): array
    {
        return OpsSite::query()
            ->with(['domains', 'assignments'])
            ->orderBy('name')
            ->get()
            ->map(function (OpsSite $site): OpsSiteRecord {
                $primaryDomain = $site->domains->firstWhere('is_primary', true);
                $domainStatuses = $site->domains
                    ->pluck('domain_status')
                    ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
                    ->map(fn (string $value): DomainPostureStatus => DomainPostureStatus::tryFrom($value) ?? DomainPostureStatus::Unsupported)
                    ->all();
                $sslStatuses = $site->domains
                    ->pluck('ssl_assignment_status')
                    ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
                    ->map(fn (string $value): SslAssignmentStatus => SslAssignmentStatus::tryFrom($value) ?? SslAssignmentStatus::Unknown)
                    ->all();

                return new OpsSiteRecord(
                    key: (string) $site->getAttribute('site_key'),
                    name: (string) $site->getAttribute('name'),
                    lifecycleStatus: SiteLifecycleStatus::tryFrom((string) $site->getAttribute('lifecycle_status')) ?? SiteLifecycleStatus::Unknown,
                    domainStatus: DomainPostureStatus::worst($domainStatuses === [] ? [DomainPostureStatus::Unsupported] : $domainStatuses),
                    sslStatus: $this->worstSslStatus($sslStatuses),
                    domainCount: $site->domains->count(),
                    primaryDomain: $primaryDomain?->getAttribute('domain'),
                    assignmentTarget: (string) ($site->assignments->first()?->getAttribute('target_reference') ?? 'Unassigned'),
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, SslAssignmentStatus>  $statuses
     */
    private function worstSslStatus(array $statuses): SslAssignmentStatus
    {
        foreach ([SslAssignmentStatus::Mismatch, SslAssignmentStatus::Missing, SslAssignmentStatus::Unknown, SslAssignmentStatus::Assigned] as $status) {
            if (in_array($status, $statuses, true)) {
                return $status;
            }
        }

        return SslAssignmentStatus::Unknown;
    }
}
