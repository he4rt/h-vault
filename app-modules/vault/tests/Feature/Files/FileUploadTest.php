<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Events\FileUploaded;
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

test('anonymous user can upload a file', function (): void {
    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->image('photo.png', 100, 100),
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['token', 'url', 'original_name', 'mime_type', 'file_size', 'status', 'is_protected', 'expires_at', 'created_at'],
        ]);

    expect(File::query()->count())->toBe(1);

    $file = File::query()->first();
    expect($file->uploaded_by)->toBeNull()
        ->and($file->status)->toBe(FileStatus::Pending);

    Storage::disk('s3')->assertExists($file->storage_path);
    Event::assertDispatched(FileUploaded::class);
});

test('authenticated user can upload a file with higher limits', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson(route('vault.files.store'), [
            'file' => UploadedFile::fake()->create('large.zip', 200 * 1024),
        ]);

    $response->assertCreated();

    $file = File::query()->first();
    expect($file->uploaded_by)->toBe($user->id);
});

test('anonymous upload rejects files exceeding size limit', function (): void {
    $maxSizeKb = config('vault.upload.max_size_mb') * 1024;

    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->create('huge.zip', $maxSizeKb + 1),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

test('upload with password creates protected file', function (): void {
    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->image('secret.png'),
        'password' => 'my-secret',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.is_protected', true);
});

test('upload with is_one_time flag', function (): void {
    $response = $this->putJson(route('vault.files.store'), [
        'file' => UploadedFile::fake()->image('once.png'),
        'is_one_time' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.is_one_time', true);
});

test('upload with custom expires_in', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson(route('vault.files.store'), [
            'file' => UploadedFile::fake()->image('timed.png'),
            'expires_in' => '24h',
        ]);

    $response->assertCreated();

    $file = File::query()->first();
    expect(now()->diffInHours($file->expires_at))->toBeBetween(23, 25);
});

test('upload without file returns validation error', function (): void {
    $response = $this->putJson(route('vault.files.store'));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});
