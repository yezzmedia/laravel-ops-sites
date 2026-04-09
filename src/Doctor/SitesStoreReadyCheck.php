<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Doctor;

use YezzMedia\Foundation\Data\DoctorResult;
use YezzMedia\Foundation\Doctor\DoctorCheck;
use YezzMedia\OpsSites\Support\OpsSitesStoreSetup;

final class SitesStoreReadyCheck implements DoctorCheck
{
    private const KEY = 'sites_store_ready';

    private const PACKAGE = 'yezzmedia/laravel-ops-sites';

    public function __construct(private readonly OpsSitesStoreSetup $storeSetup) {}

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
        $missingTables = $this->storeSetup->missingTables();

        if ($missingTables === []) {
            return $this->result('passed', 'Ops sites persistence store is ready.', false);
        }

        return $this->result('failed', 'Ops sites persistence store is missing required tables.', true, ['missing_tables' => $missingTables]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function result(string $status, string $message, bool $blocking, array $context = []): DoctorResult
    {
        return new DoctorResult(
            key: self::KEY,
            package: self::PACKAGE,
            status: $status,
            message: $message,
            isBlocking: $blocking,
            context: $context,
        );
    }
}
