<?php

declare(strict_types=1);

return [
    'rate_limits' => [
        'upload_anonymous' => (int) env('VAULT_RATE_LIMIT_UPLOAD_ANON', 10),
        'upload_authenticated' => (int) env('VAULT_RATE_LIMIT_UPLOAD_AUTH', 60),
        'download_anonymous' => (int) env('VAULT_RATE_LIMIT_DOWNLOAD_ANON', 100),
        'download_authenticated' => (int) env('VAULT_RATE_LIMIT_DOWNLOAD_AUTH', 300),
        'auth' => (int) env('VAULT_RATE_LIMIT_AUTH', 5),
    ],
    'ip_blocking' => [
        'enabled' => (bool) env('VAULT_IP_BLOCKING_ENABLED', true),
        'cache_ttl' => (int) env('VAULT_IP_BLOCK_CACHE_TTL', 3600),
    ],
];
