<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Support;

use YezzMedia\OpsSites\Data\SiteInfrastructureAssignmentRecord;
use YezzMedia\OpsSites\Models\OpsSiteAssignment;

final class SiteInfrastructureAssignmentResolver
{
    public function resolveForSite(string $siteKey): ?SiteInfrastructureAssignmentRecord
    {
        $assignment = OpsSiteAssignment::query()
            ->whereHas('site', fn ($query) => $query->where('site_key', $siteKey))
            ->orderBy('id')
            ->first();

        if ($assignment === null) {
            return null;
        }

        return new SiteInfrastructureAssignmentRecord(
            siteKey: $siteKey,
            status: SiteAssignmentStatus::tryFrom((string) $assignment->getAttribute('assignment_status')) ?? SiteAssignmentStatus::Unknown,
            target: $assignment->getAttribute('target_reference'),
            message: (string) $assignment->getAttribute('assignment_message'),
        );
    }
}
