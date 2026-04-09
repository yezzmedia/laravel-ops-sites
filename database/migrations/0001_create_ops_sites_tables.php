<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ops_sites', function (Blueprint $table): void {
            $table->id();
            $table->string('site_key')->unique();
            $table->string('name');
            $table->string('lifecycle_status')->default('unknown');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

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

            $table->unique(['site_id', 'domain']);
        });

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

    public function down(): void
    {
        Schema::dropIfExists('ops_site_assignments');
        Schema::dropIfExists('ops_site_domains');
        Schema::dropIfExists('ops_sites');
    }
};
