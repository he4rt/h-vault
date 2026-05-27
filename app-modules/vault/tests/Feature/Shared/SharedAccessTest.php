<?php

declare(strict_types=1);

use He4rt\Vault\Albums\Album;
use He4rt\Vault\Buckets\Bucket;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

test('public can view a bucket by share token', function (): void {
    $bucket = Bucket::factory()->shared()->create();

    $response = $this->getJson(route('vault.shared.bucket', ['share_token' => $bucket->share_token]));

    $response->assertOk()
        ->assertJsonPath('data.token', $bucket->token)
        ->assertJsonPath('data.is_shared', true);
});

test('returns 404 for invalid bucket share token', function (): void {
    $response = $this->getJson(route('vault.shared.bucket', ['share_token' => Str::uuid()->toString()]));

    $response->assertNotFound();
});

test('returns 404 for non-uuid bucket share token', function (): void {
    $response = $this->getJson(route('vault.shared.bucket', ['share_token' => 'not-a-uuid']));

    $response->assertNotFound();
});

test('public can view an album by share token', function (): void {
    $album = Album::factory()->shared()->create();

    $response = $this->getJson(route('vault.shared.album', ['share_token' => $album->share_token]));

    $response->assertOk()
        ->assertJsonPath('data.id', $album->id)
        ->assertJsonPath('data.is_shared', true);
});

test('returns 404 for invalid album share token', function (): void {
    $response = $this->getJson(route('vault.shared.album', ['share_token' => Str::uuid()->toString()]));

    $response->assertNotFound();
});

test('returns 404 for non-uuid album share token', function (): void {
    $response = $this->getJson(route('vault.shared.album', ['share_token' => 'not-a-uuid']));

    $response->assertNotFound();
});

test('non-shared album cannot be accessed via shared route', function (): void {
    $album = Album::factory()->create(['is_shared' => false, 'share_token' => Str::uuid()->toString()]);

    $response = $this->getJson(route('vault.shared.album', ['share_token' => $album->share_token]));

    $response->assertNotFound();
});
