<?php

declare(strict_types=1);

use He4rt\Vault\Events\FileExpired;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileExpired::class]);
});

test('cleans expired files from storage and database', function (): void {
    $expired = File::factory()->expired()->create();
    Storage::disk('s3')->put($expired->storage_path, 'content');

    $this->artisan('vault:clean-expired')
        ->assertSuccessful();

    Storage::disk('s3')->assertMissing($expired->storage_path);
    expect(File::query()->find($expired->id))->toBeNull();
    Event::assertDispatched(FileExpired::class);
});

test('does not clean non-expired files', function (): void {
    $valid = File::factory()->create(['expires_at' => now()->addDay()]);
    Storage::disk('s3')->put($valid->storage_path, 'content');

    $this->artisan('vault:clean-expired')
        ->assertSuccessful();

    Storage::disk('s3')->assertExists($valid->storage_path);
    expect(File::query()->find($valid->id))->not->toBeNull();
    Event::assertNotDispatched(FileExpired::class);
});

test('cleans expired file with thumbnail', function (): void {
    $expired = File::factory()->expired()->create(['thumbnail_path' => 'thumbnails/test.webp']);
    Storage::disk('s3')->put($expired->storage_path, 'content');
    Storage::disk('s3')->put($expired->thumbnail_path, 'thumb');

    $this->artisan('vault:clean-expired')
        ->assertSuccessful();

    Storage::disk('s3')->assertMissing($expired->storage_path);
    Storage::disk('s3')->assertMissing($expired->thumbnail_path);
});

test('handles multiple expired files in chunks', function (): void {
    File::factory()->expired()->count(5)->create();

    $this->artisan('vault:clean-expired')
        ->assertSuccessful();

    expect(File::query()->count())->toBe(0);
});
