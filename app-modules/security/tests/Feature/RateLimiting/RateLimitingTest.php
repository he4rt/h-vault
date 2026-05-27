<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

test('vault-upload rate limiter is configured', function (): void {
    expect(RateLimiter::limiter('vault-upload'))->not->toBeNull();
});

test('vault-download rate limiter is configured', function (): void {
    expect(RateLimiter::limiter('vault-download'))->not->toBeNull();
});

test('vault-auth rate limiter is configured', function (): void {
    expect(RateLimiter::limiter('vault-auth'))->not->toBeNull();
});

test('exceeding upload rate limit returns 429', function (): void {
    $ip = '1.2.3.4';
    $limit = config('security.rate_limits.upload_anonymous');

    RateLimiter::clear('vault-upload');

    for ($i = 0; $i < $limit; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->putJson(route('vault.files.store'));
    }

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->putJson(route('vault.files.store'));

    $response->assertTooManyRequests();
});
