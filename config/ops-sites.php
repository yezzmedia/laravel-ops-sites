<?php

declare(strict_types=1);

return [
    'cache' => [
        'enabled' => env('OPS_SITES_CACHE_ENABLED', true),
        'ttl' => (int) env('OPS_SITES_CACHE_TTL', 300),
        'store' => env('OPS_SITES_CACHE_STORE'),
    ],

    'audit' => [
        'enabled' => env('OPS_SITES_AUDIT_ENABLED', true),
        'driver' => env('OPS_SITES_AUDIT_DRIVER'),
        'log_name' => env('OPS_SITES_AUDIT_LOG_NAME', 'ops-sites'),
    ],

    'dns' => [
        'probe_timeout_ms' => (int) env('OPS_SITES_DNS_PROBE_TIMEOUT_MS', 750),
    ],
];
