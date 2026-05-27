<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Events\BucketCreated;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

test('authenticated user can create a bucket', function (): void {
    Event::fake([BucketCreated::class]);

    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('vault.buckets.store'), [
            'name' => 'My Bucket',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My Bucket')
        ->assertJsonStructure(['data' => ['token', 'name', 'is_shared', 'created_at']]);

    Event::assertDispatched(BucketCreated::class);
});

test('authenticated user can create a bucket without a name', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson(route('vault.buckets.store'));

    $response->assertCreated()
        ->assertJsonPath('data.name', null);
});

test('unauthenticated user cannot create a bucket', function (): void {
    $response = $this->postJson(route('vault.buckets.store'), [
        'name' => 'Unauthorized Bucket',
    ]);

    $response->assertUnauthorized();
});

test('authenticated user can view their own bucket', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(route('vault.buckets.show', ['token' => $bucket->token]));

    $response->assertOk()
        ->assertJsonPath('data.token', $bucket->token);
});

test('non-owner cannot view another user bucket', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->getJson(route('vault.buckets.show', ['token' => $bucket->token]));

    $response->assertForbidden();
});

test('unauthenticated user cannot view a bucket', function (): void {
    $bucket = Bucket::factory()->create();

    $response = $this->getJson(route('vault.buckets.show', ['token' => $bucket->token]));

    $response->assertUnauthorized();
});

test('authenticated user can delete their own bucket', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson(route('vault.buckets.destroy', ['token' => $bucket->token]));

    $response->assertNoContent();

    expect(Bucket::query()->find($bucket->id))->toBeNull();
});

test('deleting bucket nullifies associated files', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);
    $file = File::factory()->create(['bucket_id' => $bucket->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson(route('vault.buckets.destroy', ['token' => $bucket->token]));

    expect($file->fresh()->bucket_id)->toBeNull();
});

test('non-owner cannot delete another user bucket', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->deleteJson(route('vault.buckets.destroy', ['token' => $bucket->token]));

    $response->assertForbidden();
});

test('authenticated user can list files in their bucket', function (): void {
    $user = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $user->id]);
    File::factory()->count(3)->create(['bucket_id' => $bucket->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson(route('vault.buckets.files', ['token' => $bucket->token]));

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('non-owner cannot list files in another user bucket', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $bucket = Bucket::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->getJson(route('vault.buckets.files', ['token' => $bucket->token]));

    $response->assertForbidden();
});
