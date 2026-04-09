<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use YezzMedia\Foundation\Testing\FoundationTestCase;
use YezzMedia\OpsSites\OpsSitesServiceProvider;

abstract class OpsSitesTestCase extends FoundationTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            OpsSitesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        Config::set('database.default', 'testing');
        Config::set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('ops-sites.cache.enabled', false);
        Config::set('ops-sites.audit.driver', null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTablesExist();
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('migrations')) {
            Schema::create('migrations', function (Blueprint $table): void {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });
        }

        if (! Schema::hasTable('ops_sites')) {
            Schema::create('ops_sites', function (Blueprint $table): void {
                $table->id();
                $table->string('site_key')->unique();
                $table->string('name');
                $table->string('lifecycle_status')->default('unknown');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ops_site_domains')) {
            Schema::create('ops_site_domains', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('ops_sites')->cascadeOnDelete();
                $table->string('domain');
                $table->boolean('is_primary')->default(false);
                $table->string('domain_status')->default('unsupported');
                $table->text('domain_message')->default('No domain posture recorded.');
                $table->string('dns_status')->default('unsupported');
                $table->text('dns_message')->default('No DNS posture recorded.');
                $table->json('expected_dns_targets')->nullable();
                $table->json('resolved_dns_targets')->nullable();
                $table->string('ssl_assignment_status')->default('unknown');
                $table->string('certificate_reference')->nullable();
                $table->text('ssl_assignment_message')->default('No SSL assignment recorded.');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ops_site_assignments')) {
            Schema::create('ops_site_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('site_id')->constrained('ops_sites')->cascadeOnDelete();
                $table->string('assignment_status')->default('unknown');
                $table->string('target_reference')->nullable();
                $table->text('assignment_message')->default('No infrastructure assignment recorded.');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }
}
