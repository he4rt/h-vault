<?php

declare(strict_types=1);

return [
    'upload' => [
        'max_size_mb' => (int) env('VAULT_UPLOAD_LIMIT_MB', 100),
        'max_size_auth_mb' => (int) env('VAULT_UPLOAD_LIMIT_AUTH_MB', 500),
        'allowed_mimes' => explode(',', (string) env('VAULT_ALLOWED_MIMES', 'image/*,video/*,audio/*,application/pdf,application/zip')),
    ],
    'storage' => [
        'disk' => env('VAULT_STORAGE_DISK', 's3'),
        'path_prefix' => 'files',
    ],
    'ttl' => [
        'anonymous' => [
            'min_days' => (int) env('VAULT_TTL_ANON_MIN_DAYS', 1),
            'max_days' => (int) env('VAULT_TTL_ANON_MAX_DAYS', 7),
        ],
        'authenticated' => [
            'min_days' => (int) env('VAULT_TTL_AUTH_MIN_DAYS', 1),
            'max_days' => (int) env('VAULT_TTL_AUTH_MAX_DAYS', 30),
        ],
        'custom_override' => (bool) env('VAULT_TTL_CUSTOM_OVERRIDE', true),
    ],
    'rate_limit' => [
        'anonymous' => (int) env('VAULT_RATE_LIMIT_ANON', 10),
        'authenticated' => (int) env('VAULT_RATE_LIMIT_AUTH', 60),
    ],
];
