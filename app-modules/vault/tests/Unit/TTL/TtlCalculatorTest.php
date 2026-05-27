<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\TTL\TtlCalculator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('tiny file gets maximum TTL for anonymous', function (): void {
    $calculator = new TtlCalculator();
    $ttl = $calculator->calculate(1024); // 1KB

    // With defaults: min=1, max=7, should be close to 7
    expect($ttl)->toBe(7);
});

test('max size file gets minimum TTL for anonymous', function (): void {
    $calculator = new TtlCalculator();
    $maxBytes = config('vault.upload.max_size_mb') * 1024 * 1024;
    $ttl = $calculator->calculate($maxBytes);

    expect($ttl)->toBe(config()->integer('vault.ttl.anonymous.min_days'));
});

test('half size file gets intermediate TTL', function (): void {
    $calculator = new TtlCalculator();
    $halfBytes = (int) (config('vault.upload.max_size_mb') * 1024 * 1024 * 0.5);
    $ttl = $calculator->calculate($halfBytes);

    $minDays = config()->integer('vault.ttl.anonymous.min_days');
    $maxDays = config()->integer('vault.ttl.anonymous.max_days');

    expect($ttl)->toBeGreaterThan($minDays)
        ->and($ttl)->toBeLessThanOrEqual($maxDays);
});

test('authenticated user gets higher TTL range', function (): void {
    $user = User::factory()->create();
    $calculator = new TtlCalculator();
    $ttl = $calculator->calculate(1024, $user); // 1KB

    expect($ttl)->toBe(config()->integer('vault.ttl.authenticated.max_days'));
});

test('calculateExpiresAt returns future datetime', function (): void {
    $calculator = new TtlCalculator();
    $expiresAt = $calculator->calculateExpiresAt(1024);

    expect($expiresAt->getTimestamp())->toBeGreaterThan(now()->getTimestamp());
});
