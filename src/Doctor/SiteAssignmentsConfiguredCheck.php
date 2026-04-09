<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Doctor;

use YezzMedia\Foundation\Data\DoctorResult;
use YezzMedia\Foundation\Doctor\DoctorCheck;
use YezzMedia\OpsSites\Support\OpsSitesManager;

final class SiteAssignmentsConfiguredCheck implements DoctorCheck
{
    private const KEY = 'site_assignments_configured';

    private const PACKAGE = 'yezzmedia/laravel-ops-sites';

    public function __construct(private readonly OpsSitesManager $manager) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function package(): string
    {
        return self::PACKAGE;
    }

    public function run(): DoctorResult
    {
        $missing = collect($this->manager->sites())
            ->filter(fn ($site): bool => $site->assignmentTarget === 'Unassigned')
            ->pluck('key')
            ->values()
            ->all();

        if ($missing === []) {
            return $this->result('passed', 'Every tracked site has an infrastructure assignment.', false);
        }

        return $this->result('warning', 'Some tracked sites do not have an infrastructure assignment.', false, ['site_keys' => $missing]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function result(string $status, string $message, bool $blocking, array $context = []): DoctorResult
    {
        return new DoctorResult(self::KEY, self::PACKAGE, $status, $message, $blocking, $context);
    }
}
