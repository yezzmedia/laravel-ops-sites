<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Doctor;

use YezzMedia\Foundation\Data\DoctorResult;
use YezzMedia\Foundation\Doctor\DoctorCheck;
use YezzMedia\OpsSites\Support\DomainPostureStatus;
use YezzMedia\OpsSites\Support\OpsSitesManager;

final class DnsTargetsResolvableCheck implements DoctorCheck
{
    private const KEY = 'dns_targets_resolvable';

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
        $problemDomains = [];

        foreach ($this->manager->sites() as $site) {
            foreach ($this->manager->dnsPostureFor($site->key) as $record) {
                if (in_array($record->status, [DomainPostureStatus::Failed, DomainPostureStatus::Drifted], true)) {
                    $problemDomains[] = $record->domain;
                }
            }
        }

        if ($problemDomains === []) {
            return $this->result('passed', 'DNS posture is resolvable for all tracked site domains.', false);
        }

        return $this->result('warning', 'Some site domains have unresolved or drifted DNS targets.', false, ['domains' => $problemDomains]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function result(string $status, string $message, bool $blocking, array $context = []): DoctorResult
    {
        return new DoctorResult(self::KEY, self::PACKAGE, $status, $message, $blocking, $context);
    }
}
