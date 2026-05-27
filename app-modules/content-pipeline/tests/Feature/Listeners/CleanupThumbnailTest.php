<?php

declare(strict_types=1);

use He4rt\ContentPipeline\Listeners\CleanupThumbnailListener;
use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Events\FileExpired;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
});

test('cleanup removes thumbnail on FileExpired', function (): void {
    $file = File::factory()->expired()->create(['thumbnail_path' => 'thumbnails/2026/05/test_thumb.webp']);
    Storage::disk('s3')->put($file->thumbnail_path, 'thumb-content');

    $listener = new CleanupThumbnailListener();
    $listener->handle(new FileExpired($file));

    Storage::disk('s3')->assertMissing($file->thumbnail_path);
});

test('cleanup removes thumbnail on FileDeleted', function (): void {
    $file = File::factory()->create(['thumbnail_path' => 'thumbnails/2026/05/test_thumb.webp']);
    Storage::disk('s3')->put($file->thumbnail_path, 'thumb-content');

    $listener = new CleanupThumbnailListener();
    $listener->handle(new FileDeleted($file));

    Storage::disk('s3')->assertMissing($file->thumbnail_path);
});

test('cleanup does nothing when thumbnail_path is null on FileExpired', function (): void {
    $file = File::factory()->expired()->create(['thumbnail_path' => null]);

    $listener = new CleanupThumbnailListener();

    // Should not throw
    $listener->handle(new FileExpired($file));

    expect(true)->toBeTrue();
});

test('cleanup does nothing when thumbnail_path is null on FileDeleted', function (): void {
    $file = File::factory()->create(['thumbnail_path' => null]);

    $listener = new CleanupThumbnailListener();

    // Should not throw
    $listener->handle(new FileDeleted($file));

    expect(true)->toBeTrue();
});
