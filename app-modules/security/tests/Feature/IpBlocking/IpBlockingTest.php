<?php

declare(strict_types=1);

use He4rt\Security\IpBlocking\BlockedIp;
use He4rt\Security\IpBlocking\BlockIpAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['security.ip_blocking.enabled' => true]);
    Cache::flush();
});

test('blocked IP receives 403 on any request', function (): void {
    $ip = '1.2.3.4';

    BlockedIp::factory()->create([
        'ip' => $ip,
        'reason' => 'abuse',
        'blocked_until' => now()->addDay(),
    ]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->putJson(route('vault.files.store'));

    $response->assertForbidden();
});

test('non-blocked IP passes through the middleware', function (): void {
    $response = $this->withServerVariables(['REMOTE_ADDR' => '9.9.9.9'])
        ->putJson(route('vault.files.store'));

    expect($response->status())->not->toBe(403);
});

test('expired block allows access and cleans up the record', function (): void {
    $ip = '5.6.7.8';

    BlockedIp::factory()->expired()->create(['ip' => $ip, 'reason' => 'old block']);

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->putJson(route('vault.files.store'));

    expect($response->status())->not->toBe(403)
        ->and(BlockedIp::query()->where('ip', $ip)->exists())->toBeFalse();
});

test('permanent block (null blocked_until) always blocks access', function (): void {
    $ip = '10.0.0.1';

    BlockedIp::factory()->permanent()->create(['ip' => $ip, 'reason' => 'permanent ban']);

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->putJson(route('vault.files.store'));

    $response->assertForbidden();
});

test('IP blocking middleware is bypassed when disabled', function (): void {
    config(['security.ip_blocking.enabled' => false]);
    $ip = '2.3.4.5';

    BlockedIp::factory()->create([
        'ip' => $ip,
        'reason' => 'should be ignored',
        'blocked_until' => now()->addDay(),
    ]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->putJson(route('vault.files.store'));

    expect($response->status())->not->toBe(403);
});

test('BlockIpAction creates a new blocked IP record', function (): void {
    $action = resolve(BlockIpAction::class);

    $blockedIp = $action->handle('192.168.1.1', 'test reason', now()->addHours(24));

    expect($blockedIp)->toBeInstanceOf(BlockedIp::class)
        ->and($blockedIp->ip)->toBe('192.168.1.1')
        ->and($blockedIp->reason)->toBe('test reason')
        ->and($blockedIp->blocked_until)->not->toBeNull();
});

test('BlockIpAction updates an existing blocked IP record', function (): void {
    $ip = '192.168.2.2';
    $existing = BlockedIp::factory()->create(['ip' => $ip, 'reason' => 'original reason']);

    $action = resolve(BlockIpAction::class);
    $updated = $action->handle($ip, 'updated reason');

    expect($updated->id)->toBe($existing->id)
        ->and($updated->reason)->toBe('updated reason')
        ->and($updated->isPermanent())->toBeTrue();
});

test('BlockIpAction invalidates the cache for the blocked IP', function (): void {
    $ip = '192.168.3.3';
    $cacheKey = 'blocked_ip:'.$ip;

    Cache::put($cacheKey, false, 3600);

    $action = resolve(BlockIpAction::class);
    $action->handle($ip, 'late block');

    expect(Cache::has($cacheKey))->toBeFalse();
});

test('BlockedIp isExpired returns true for past blocked_until', function (): void {
    $blockedIp = BlockedIp::factory()->expired()->make();

    expect($blockedIp->isExpired())->toBeTrue();
});

test('BlockedIp isExpired returns false for future blocked_until', function (): void {
    $blockedIp = BlockedIp::factory()->make(['blocked_until' => now()->addDay()]);

    expect($blockedIp->isExpired())->toBeFalse();
});

test('BlockedIp isPermanent returns true when blocked_until is null', function (): void {
    $blockedIp = BlockedIp::factory()->permanent()->make();

    expect($blockedIp->isPermanent())->toBeTrue();
});
