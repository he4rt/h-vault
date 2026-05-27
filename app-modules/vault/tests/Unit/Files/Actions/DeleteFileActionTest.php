<?php

declare(strict_types=1);

use He4rt\Vault\Events\FileDeleted;
use He4rt\Vault\Files\Actions\DeleteFileAction;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileDeleted::class]);
});

test('deletes file from storage and database', function (): void {
    $file = File::factory()->create();
    Storage::disk('s3')->put($file->storage_path, 'file-content');

    $action = resolve(DeleteFileAction::class);
    $action->handle($file);

    Storage::disk('s3')->assertMissing($file->storage_path);
    expect(File::withTrashed()->find($file->id))->toBeNull();
    Event::assertDispatched(fn (FileDeleted $e) => $e->file->id === $file->id);
});

test('deletes file with thumbnail from storage', function (): void {
    $file = File::factory()->create(['thumbnail_path' => 'thumbnails/2026/05/test_thumb.webp']);
    Storage::disk('s3')->put($file->storage_path, 'file-content');
    Storage::disk('s3')->put($file->thumbnail_path, 'thumb-content');

    $action = resolve(DeleteFileAction::class);
    $action->handle($file);

    Storage::disk('s3')->assertMissing($file->storage_path);
    Storage::disk('s3')->assertMissing($file->thumbnail_path);
});
