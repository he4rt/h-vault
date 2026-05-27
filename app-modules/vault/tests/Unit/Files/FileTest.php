<?php

declare(strict_types=1);

use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileStatusChanged;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

test('file factory creates valid file', function (): void {
    $file = File::factory()->create();

    expect($file)->toBeInstanceOf(File::class)
        ->and($file->token)->not->toBeEmpty()
        ->and($file->original_name)->not->toBeEmpty()
        ->and($file->storage_path)->not->toBeEmpty()
        ->and($file->mime_type)->not->toBeEmpty()
        ->and($file->file_size)->toBeGreaterThan(0)
        ->and($file->status)->toBe(FileStatus::Pending);
});

test('isProtected returns true when password_hash is set', function (): void {
    $file = File::factory()->protected()->create();

    expect($file->isProtected())->toBeTrue();
});

test('isProtected returns false when password_hash is null', function (): void {
    $file = File::factory()->create();

    expect($file->isProtected())->toBeFalse();
});

test('isExpired returns true when expires_at is in the past', function (): void {
    $file = File::factory()->expired()->create();

    expect($file->isExpired())->toBeTrue();
});

test('isExpired returns false when expires_at is in the future', function (): void {
    $file = File::factory()->create(['expires_at' => now()->addDay()]);

    expect($file->isExpired())->toBeFalse();
});

test('changeStatus updates status and dispatches event', function (): void {
    Event::fake([FileStatusChanged::class]);

    $file = File::factory()->create(['status' => FileStatus::Pending]);

    $file->changeStatus(FileStatus::Clean);

    expect($file->fresh()->status)->toBe(FileStatus::Clean);

    Event::assertDispatched(fn (FileStatusChanged $e) => $e->file->id === $file->id
        && $e->oldStatus === FileStatus::Pending
        && $e->newStatus === FileStatus::Clean);
});

test('uploader relation returns user', function (): void {
    $file = File::factory()->authenticated()->create();

    expect($file->uploader)->not->toBeNull()
        ->and($file->uploader->id)->toBe($file->uploaded_by);
});

test('uploader relation returns null for anonymous file', function (): void {
    $file = File::factory()->create();

    expect($file->uploader)->toBeNull();
});
