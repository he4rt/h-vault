<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Albums\Album;
use He4rt\Vault\Buckets\Bucket;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('authenticated user can create an album in their bucket', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('vault.albums.store', ['bucket' => $bucket->token]), [
            'name' => 'My Album',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My Album')
        ->assertJsonPath('data.bucket_token', $bucket->token)
        ->assertJsonStructure(['data' => ['id', 'bucket_token', 'name', 'is_shared', 'share_token']]);
});

test('album name is required', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('vault.albums.store', ['bucket' => $bucket->token]), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('non-owner cannot create an album in another user bucket', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->postJson(route('vault.albums.store', ['bucket' => $bucket->token]), [
            'name' => 'Hijacked Album',
        ]);

    $response->assertForbidden();
});

test('unauthenticated user cannot create an album', function (): void {
    $bucket = Bucket::factory()->create();

    $response = $this->postJson(route('vault.albums.store', ['bucket' => $bucket->token]), [
        'name' => 'Album',
    ]);

    $response->assertUnauthorized();
});

test('authenticated user can view their album', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['bucket_id' => $bucket->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(route('vault.albums.show', ['bucket' => $bucket->token, 'album' => $album->id]));

    $response->assertOk()
        ->assertJsonPath('data.id', $album->id);
});

test('non-owner cannot view album in another user bucket', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $owner->id]);
    $album = Album::factory()->create(['bucket_id' => $bucket->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->getJson(route('vault.albums.show', ['bucket' => $bucket->token, 'album' => $album->id]));

    $response->assertForbidden();
});

test('authenticated user can delete their album', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);
    $album = Album::factory()->create(['bucket_id' => $bucket->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson(route('vault.albums.destroy', ['bucket' => $bucket->token, 'album' => $album->id]));

    $response->assertNoContent();

    expect(Album::query()->find($album->id))->toBeNull();
});

test('non-owner cannot delete album in another user bucket', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $owner->id]);
    $album = Album::factory()->create(['bucket_id' => $bucket->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->deleteJson(route('vault.albums.destroy', ['bucket' => $bucket->token, 'album' => $album->id]));

    $response->assertForbidden();
});
