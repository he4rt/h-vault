<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
use He4rt\Vault\Files\Actions\StoreFileAction;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileUploaded::class]);
});

test('stores file anonymously', function (): void {
    $uploadedFile = UploadedFile::fake()->image('photo.png', 100, 100);

    $action = resolve(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
    );

    expect($file)->toBeInstanceOf(File::class)
        ->and($file->original_name)->toBe('photo.png')
        ->and($file->mime_type)->toBe('image/png')
        ->and($file->file_size)->toBeGreaterThan(0)
        ->and($file->uploaded_by)->toBeNull()
        ->and($file->status)->toBe(FileStatus::Pending)
        ->and($file->is_one_time)->toBeFalse()
        ->and($file->token)->not->toBeEmpty()
        ->and($file->expires_at)->not->toBeNull();

    Storage::disk('s3')->assertExists($file->storage_path);
    Event::assertDispatched(fn (FileUploaded $e) => $e->file->id === $file->id);
});

test('stores file with authenticated user', function (): void {
    $user = User::factory()->create();
    $uploadedFile = UploadedFile::fake()->image('avatar.jpg');

    $action = resolve(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
        user: $user,
    );

    expect($file->uploaded_by)->toBe($user->id);
});

test('stores file with password', function (): void {
    $uploadedFile = UploadedFile::fake()->image('secret.png');

    $action = resolve(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
        password: 'my-secret',
    );

    expect($file->password_hash)->not->toBeNull()
        ->and($file->isProtected())->toBeTrue();
});

test('stores file as one-time', function (): void {
    $uploadedFile = UploadedFile::fake()->image('once.png');

    $action = resolve(StoreFileAction::class);
    $file = $action->handle(
        uploadedFile: $uploadedFile,
        isOneTime: true,
    );

    expect($file->is_one_time)->toBeTrue();
});
