<?php

declare(strict_types=1);

use He4rt\ContentPipeline\Events\ThumbnailGenerated;
use He4rt\ContentPipeline\Thumbnails\GenerateThumbnailJob;
use He4rt\ContentPipeline\Thumbnails\GenerateThumbnailListener;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([ThumbnailGenerated::class]);
});

test('GenerateThumbnailListener dispatches job for image files', function (): void {
    Queue::fake();

    $file = File::factory()->withMime('image/png')->create();
    $event = new FileUploaded($file);

    $listener = new GenerateThumbnailListener();
    $listener->handle($event);

    Queue::assertPushed(GenerateThumbnailJob::class, fn ($job) => $job->file->id === $file->id);
});

test('GenerateThumbnailListener skips non-image files', function (): void {
    Queue::fake();

    $file = File::factory()->withMime('application/pdf')->create();
    $event = new FileUploaded($file);

    $listener = new GenerateThumbnailListener();
    $listener->handle($event);

    Queue::assertNothingPushed();
});

test('GenerateThumbnailListener skips video files', function (): void {
    Queue::fake();

    $file = File::factory()->withMime('video/mp4')->create();
    $event = new FileUploaded($file);

    $listener = new GenerateThumbnailListener();
    $listener->handle($event);

    Queue::assertNothingPushed();
});

test('GenerateThumbnailJob writes thumbnail to S3 and updates file', function (): void {
    $file = File::factory()->withMime('image/png')->create();
    Storage::disk('s3')->put($file->storage_path, 'fake-image-content');

    dispatch_sync(new GenerateThumbnailJob($file));

    $file->refresh();

    expect($file->thumbnail_path)->not->toBeNull()
        ->and($file->thumbnail_path)->toStartWith('thumbnails/');

    Storage::disk('s3')->assertExists($file->thumbnail_path);
    Event::assertDispatched(fn (ThumbnailGenerated $e) => $e->file->id === $file->id
        && $e->thumbnailPath === $file->thumbnail_path);
});
