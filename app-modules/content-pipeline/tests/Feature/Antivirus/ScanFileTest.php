<?php

declare(strict_types=1);

use He4rt\ContentPipeline\Antivirus\ScanFileJob;
use He4rt\ContentPipeline\Antivirus\ScanFileListener;
use He4rt\ContentPipeline\Events\FileScanCompleted;
use He4rt\ContentPipeline\Listeners\UpdateFileStatusListener;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileScanCompleted::class]);
});

test('ScanFileListener dispatches ScanFileJob', function (): void {
    Queue::fake();

    $file = File::factory()->create();
    $event = new FileUploaded($file);

    $listener = new ScanFileListener();
    $listener->handle($event);

    Queue::assertPushed(ScanFileJob::class, fn ($job) => $job->file->id === $file->id);
});

test('ScanFileJob with NullScanner dispatches FileScanCompleted with clean=true', function (): void {
    $file = File::factory()->create();

    dispatch_sync(new ScanFileJob($file));

    Event::assertDispatched(fn (FileScanCompleted $e) => $e->file->id === $file->id
        && $e->scanner === 'null'
        && $e->clean);
});

test('UpdateFileStatusListener sets status to clean when scan is clean', function (): void {
    Event::fake();

    $file = File::factory()->create(['status' => FileStatus::Pending]);
    $event = new FileScanCompleted($file, 'null', true);

    $listener = new UpdateFileStatusListener();
    $listener->handle($event);

    expect($file->fresh()->status)->toBe(FileStatus::Clean);
});

test('UpdateFileStatusListener sets status to infected when scan is dirty', function (): void {
    Event::fake();

    $file = File::factory()->create(['status' => FileStatus::Pending]);
    $event = new FileScanCompleted($file, 'null', false);

    $listener = new UpdateFileStatusListener();
    $listener->handle($event);

    expect($file->fresh()->status)->toBe(FileStatus::Infected);
});
