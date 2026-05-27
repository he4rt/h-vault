<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

test('can get file info by token', function (): void {
    $file = File::factory()->create();

    $response = $this->getJson(route('vault.files.show', ['token' => $file->token]));

    $response->assertOk()
        ->assertJsonPath('data.token', $file->token)
        ->assertJsonPath('data.original_name', $file->original_name);
});

test('returns 404 for non-existent token', function (): void {
    $response = $this->getJson(route('vault.files.show', ['token' => 'non-existent']));

    $response->assertNotFound();
});

test('owner can update file password', function (): void {
    $user = User::factory()->create();
    $file = File::factory()->create(['uploaded_by' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->patchJson(route('vault.files.update', ['token' => $file->token]), [
            'password' => 'new-password',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.is_protected', true);
});

test('non-owner cannot update file', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $file = File::factory()->create(['uploaded_by' => $owner->id]);

    $response = $this->actingAs($other, 'sanctum')
        ->patchJson(route('vault.files.update', ['token' => $file->token]), [
            'password' => 'hack',
        ]);

    $response->assertForbidden();
});

test('owner can delete file', function (): void {
    Storage::fake('s3');
    Event::fake([FileDeleted::class]);

    $user = User::factory()->create();
    $file = File::factory()->create(['uploaded_by' => $user->id]);
    Storage::disk('s3')->put($file->storage_path, 'content');

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson(route('vault.files.destroy', ['token' => $file->token]));

    $response->assertNoContent();

    expect(File::query()->find($file->id))->toBeNull();
    Event::assertDispatched(FileDeleted::class);
});

test('unauthenticated user cannot delete file', function (): void {
    $file = File::factory()->create(['uploaded_by' => User::factory()->create()->id]);

    $response = $this->deleteJson(route('vault.files.destroy', ['token' => $file->token]));

    $response->assertUnauthorized();
});
