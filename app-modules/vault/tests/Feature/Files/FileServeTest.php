<?php

declare(strict_types=1);

use He4rt\Vault\Events\FileDownloaded;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    Event::fake([FileDownloaded::class]);
});

test('can download a file by token', function (): void {
    $file = File::factory()->create([
        'mime_type' => 'image/png',
    ]);
    Storage::disk('s3')->put($file->storage_path, 'test-file-content');

    $response = $this->get(route('vault.files.serve', [
        'token' => $file->token,
        'filename' => $file->original_name,
    ]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

test('returns 404 for non-existent token', function (): void {
    $response = $this->get(route('vault.files.serve', [
        'token' => '00000000-0000-0000-0000-000000000000',
        'filename' => 'file.txt',
    ]));

    $response->assertNotFound();
});

test('returns 404 for expired file', function (): void {
    $file = File::factory()->expired()->create();
    Storage::disk('s3')->put($file->storage_path, 'content');

    $response = $this->get(route('vault.files.serve', [
        'token' => $file->token,
        'filename' => $file->original_name,
    ]));

    $response->assertNotFound();
});

test('returns 403 for password-protected file without password header', function (): void {
    $file = File::factory()->create([
        'password_hash' => Hash::make('correct-password'),
    ]);
    Storage::disk('s3')->put($file->storage_path, 'content');

    $response = $this->get(route('vault.files.serve', [
        'token' => $file->token,
        'filename' => $file->original_name,
    ]));

    $response->assertForbidden();
});

test('returns 403 for password-protected file with wrong password', function (): void {
    $file = File::factory()->create([
        'password_hash' => Hash::make('correct-password'),
    ]);
    Storage::disk('s3')->put($file->storage_path, 'content');

    $response = $this->withHeader('X-File-Password', 'wrong-password')
        ->get(route('vault.files.serve', [
            'token' => $file->token,
            'filename' => $file->original_name,
        ]));

    $response->assertForbidden();
});

test('can download password-protected file with correct password', function (): void {
    $file = File::factory()->create([
        'password_hash' => Hash::make('correct-password'),
        'mime_type' => 'image/png',
    ]);
    Storage::disk('s3')->put($file->storage_path, 'protected-content');

    $response = $this->withHeader('X-File-Password', 'correct-password')
        ->get(route('vault.files.serve', [
            'token' => $file->token,
            'filename' => $file->original_name,
        ]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/png');
});

test('one-time file is soft-deleted after download', function (): void {
    $file = File::factory()->oneTime()->create();
    Storage::disk('s3')->put($file->storage_path, 'one-time-content');

    $firstResponse = $this->get(route('vault.files.serve', [
        'token' => $file->token,
        'filename' => $file->original_name,
    ]));
    $firstResponse->assertOk();

    expect(File::withTrashed()->find($file->id)?->trashed())->toBeTrue();

    $secondResponse = $this->get(route('vault.files.serve', [
        'token' => $file->token,
        'filename' => $file->original_name,
    ]));
    $secondResponse->assertStatus(410);
});

test('FileDownloaded event is dispatched after successful download', function (): void {
    $file = File::factory()->create();
    Storage::disk('s3')->put($file->storage_path, 'content');

    $this->get(route('vault.files.serve', [
        'token' => $file->token,
        'filename' => $file->original_name,
    ]))->assertOk();

    Event::assertDispatched(fn (FileDownloaded $event): bool => $event->file->id === $file->id);
});
