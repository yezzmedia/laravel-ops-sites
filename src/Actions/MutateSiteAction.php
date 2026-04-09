<?php

declare(strict_types=1);

namespace YezzMedia\OpsSites\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use YezzMedia\OpsSites\Events\SiteMutated;
use YezzMedia\OpsSites\Models\OpsSite;
use YezzMedia\OpsSites\Models\OpsSiteAssignment;
use YezzMedia\OpsSites\Support\OpsSitesManager;
use YezzMedia\OpsSites\Support\SiteAssignmentStatus;
use YezzMedia\OpsSites\Support\SiteLifecycleStatus;

final class MutateSiteAction
{
    public function __construct(
        private readonly OpsSitesManager $manager,
        private readonly Dispatcher $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, string $source = 'manual'): OpsSite
    {
        return $this->persist(null, $data, 'created', $source);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OpsSite $site, array $data, string $source = 'manual'): OpsSite
    {
        return $this->persist($site, $data, 'updated', $source);
    }

    public function archive(OpsSite $site, string $source = 'manual'): OpsSite
    {
        $site->forceFill([
            'lifecycle_status' => SiteLifecycleStatus::Archived->value,
        ])->save();

        $site = $site->fresh(['domains', 'assignments']) ?? $site;

        $this->manager->refresh();

        $this->events->dispatch(new SiteMutated(
            action: 'archived',
            siteKey: (string) $site->getAttribute('site_key'),
            siteName: (string) $site->getAttribute('name'),
            lifecycleStatus: (string) $site->getAttribute('lifecycle_status'),
            domains: $site->domains->pluck('domain')->map(fn (mixed $domain): string => (string) $domain)->values()->all(),
            assignmentStatus: (string) ($site->assignments->first()?->getAttribute('assignment_status') ?? SiteAssignmentStatus::Unknown->value),
            assignmentTarget: $site->assignments->first()?->getAttribute('target_reference'),
            actorId: Auth::id(),
            source: $source,
            completedAt: now()->toIso8601String(),
        ));

        return $site;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(?OpsSite $site, array $data, string $action, string $source): OpsSite
    {
        $normalized = $this->normalize($data, $site);
        $validated = $this->validate($normalized, $site);

        /** @var OpsSite $persisted */
        $persisted = DB::transaction(function () use ($site, $validated): OpsSite {
            $site ??= new OpsSite;

            $site->forceFill([
                'site_key' => $validated['site_key'],
                'name' => $validated['name'],
                'lifecycle_status' => $validated['lifecycle_status'],
                'metadata' => $validated['metadata'],
            ])->save();

            $site->domains()->delete();

            foreach ($validated['domains'] as $domain) {
                $site->domains()->create($this->domainAttributes($domain));
            }

            $assignment = $site->assignments()->orderBy('id')->first() ?? new OpsSiteAssignment;

            $assignment->site()->associate($site);
            $assignment->forceFill($this->assignmentAttributes($validated))->save();

            $site->assignments()
                ->whereKeyNot($assignment->getKey())
                ->delete();

            return $site->fresh(['domains', 'assignments']) ?? $site;
        });

        $this->manager->refresh();

        $this->events->dispatch(new SiteMutated(
            action: $action,
            siteKey: (string) $persisted->getAttribute('site_key'),
            siteName: (string) $persisted->getAttribute('name'),
            lifecycleStatus: (string) $persisted->getAttribute('lifecycle_status'),
            domains: $persisted->domains->pluck('domain')->map(fn (mixed $domain): string => (string) $domain)->values()->all(),
            assignmentStatus: (string) ($persisted->assignments->first()?->getAttribute('assignment_status') ?? SiteAssignmentStatus::Unknown->value),
            assignmentTarget: $persisted->assignments->first()?->getAttribute('target_reference'),
            actorId: Auth::id(),
            source: $source,
            completedAt: now()->toIso8601String(),
        ));

        return $persisted;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, ?OpsSite $site): array
    {
        $domains = array_values(array_map(function (mixed $domain): array {
            $domain = is_array($domain) ? $domain : [];

            return [
                'domain' => Str::lower(trim((string) ($domain['domain'] ?? ''))),
                'is_primary' => (bool) ($domain['is_primary'] ?? false),
                'expected_dns_targets' => $this->splitTargets((string) ($domain['expected_dns_targets'] ?? '')),
                'resolved_dns_targets' => $this->splitTargets((string) ($domain['resolved_dns_targets'] ?? '')),
                'certificate_reference' => trim((string) ($domain['certificate_reference'] ?? '')),
            ];
        }, is_array($data['domains'] ?? null) ? $data['domains'] : []));

        return [
            'site_key' => Str::lower(trim((string) ($data['site_key'] ?? $site?->getAttribute('site_key') ?? ''))),
            'name' => trim((string) ($data['name'] ?? '')),
            'lifecycle_status' => trim((string) ($data['lifecycle_status'] ?? SiteLifecycleStatus::Unknown->value)),
            'metadata' => $this->decodeJson((string) ($data['metadata_json'] ?? ''), 'metadata_json'),
            'domains' => $domains,
            'assignment_target' => trim((string) ($data['assignment_target'] ?? '')),
            'assignment_metadata' => $this->decodeJson((string) ($data['assignment_metadata_json'] ?? ''), 'assignment_metadata_json'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?OpsSite $site): array
    {
        $validator = Validator::make($data, [
            'site_key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9][a-z0-9_-]*$/',
                Rule::unique('ops_sites', 'site_key')->ignore($site?->getKey()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'lifecycle_status' => [
                'required',
                Rule::in(array_map(fn (SiteLifecycleStatus $status): string => $status->value, SiteLifecycleStatus::cases())),
            ],
            'metadata' => ['nullable', 'array'],
            'domains' => ['required', 'array', 'min:1'],
            'domains.*.domain' => ['required', 'string', 'max:255'],
            'domains.*.is_primary' => ['required', 'boolean'],
            'domains.*.expected_dns_targets' => ['nullable', 'array'],
            'domains.*.resolved_dns_targets' => ['nullable', 'array'],
            'domains.*.certificate_reference' => ['nullable', 'string', 'max:255'],
            'assignment_target' => ['nullable', 'string', 'max:255'],
            'assignment_metadata' => ['nullable', 'array'],
        ]);

        $validator->after(function ($validator) use ($data): void {
            $domains = $data['domains'];
            $primaryCount = count(array_filter($domains, fn (array $domain): bool => $domain['is_primary']));
            $uniqueDomains = array_unique(array_map(fn (array $domain): string => $domain['domain'], $domains));

            if ($primaryCount !== 1) {
                $validator->errors()->add('domains', 'Exactly one primary domain must be configured for a site.');
            }

            if (count($uniqueDomains) !== count($domains)) {
                $validator->errors()->add('domains', 'Domains must be unique within a site.');
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array{domain: string, is_primary: bool, expected_dns_targets: array<int, string>, resolved_dns_targets: array<int, string>, certificate_reference: string}  $domain
     * @return array<string, mixed>
     */
    private function domainAttributes(array $domain): array
    {
        $expectedTargets = $this->sortedTargets($domain['expected_dns_targets']);
        $resolvedTargets = $this->sortedTargets($domain['resolved_dns_targets']);

        if ($expectedTargets === [] && $resolvedTargets === []) {
            $dnsStatus = 'unsupported';
            $dnsMessage = 'No DNS posture recorded.';
        } elseif ($expectedTargets === [] || $resolvedTargets === []) {
            $dnsStatus = 'warning';
            $dnsMessage = 'DNS targets are only partially configured.';
        } elseif ($expectedTargets === $resolvedTargets) {
            $dnsStatus = 'healthy';
            $dnsMessage = 'Resolved DNS targets match the expected targets.';
        } else {
            $dnsStatus = 'drifted';
            $dnsMessage = 'Resolved DNS targets do not match the expected targets.';
        }

        $certificateReference = $domain['certificate_reference'];

        return [
            'domain' => $domain['domain'],
            'is_primary' => $domain['is_primary'],
            'domain_status' => 'healthy',
            'domain_message' => $domain['is_primary']
                ? 'Primary domain mapping is configured.'
                : 'Domain mapping is configured.',
            'dns_status' => $dnsStatus,
            'dns_message' => $dnsMessage,
            'expected_dns_targets' => $expectedTargets === [] ? null : $expectedTargets,
            'resolved_dns_targets' => $resolvedTargets === [] ? null : $resolvedTargets,
            'ssl_assignment_status' => filled($certificateReference) ? 'assigned' : 'missing',
            'certificate_reference' => filled($certificateReference) ? $certificateReference : null,
            'ssl_assignment_message' => filled($certificateReference)
                ? 'Certificate reference is assigned.'
                : 'No SSL assignment recorded.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function assignmentAttributes(array $validated): array
    {
        $target = $validated['assignment_target'];

        return [
            'assignment_status' => filled($target) ? SiteAssignmentStatus::Assigned->value : SiteAssignmentStatus::Missing->value,
            'target_reference' => filled($target) ? $target : null,
            'assignment_message' => filled($target)
                ? sprintf('Assigned to %s.', $target)
                : 'No infrastructure assignment is currently configured.',
            'metadata' => $validated['assignment_metadata'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function splitTargets(string $value): array
    {
        return array_values(array_filter(array_unique(array_map(
            static fn (string $target): string => trim($target),
            preg_split('/[\r\n,]+/', $value) ?: [],
        )), static fn (string $target): bool => $target !== ''));
    }

    /**
     * @return array<int, string>|null
     */
    private function decodeJson(string $value, string $field): ?array
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages([
                $field => 'The value must be valid JSON.',
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'The value must decode to a JSON object or array.',
            ]);
        }

        return $decoded;
    }

    /**
     * @param  array<int, string>  $targets
     * @return array<int, string>
     */
    private function sortedTargets(array $targets): array
    {
        sort($targets, SORT_NATURAL);

        return $targets;
    }
}
