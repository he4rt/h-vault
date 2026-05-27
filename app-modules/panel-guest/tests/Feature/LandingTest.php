<?php

declare(strict_types=1);

use He4rt\PanelGuest\Livewire\Landing;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

uses(LazilyRefreshDatabase::class);

test('landing page renders at root URL', function (): void {
    $response = $this->get(route('guest.landing'));

    $response->assertOk();
    $response->assertSeeLivewire(Landing::class);
});

test('landing page displays upload form', function (): void {
    livewire(Landing::class)
        ->assertSee('Choose a file')
        ->assertSee('Upload')
        ->assertSee('Password protection')
        ->assertSee('Expires in');
});

test('landing page displays file counter', function (): void {
    File::factory()->count(3)->create();

    livewire(Landing::class)
        ->assertSee('3');
});

test('file upload requires a file', function (): void {
    livewire(Landing::class)
        ->call('upload')
        ->assertHasErrors(['file' => 'required']);
});

test('file upload succeeds with valid file', function (): void {
    Storage::fake(config('vault.storage.disk'));

    $upload = UploadedFile::fake()->image('test-image.png', 640, 480);

    livewire(Landing::class)
        ->set('file', $upload)
        ->set('expiresIn', '24h')
        ->call('upload')
        ->assertHasNoErrors()
        ->assertSet('uploadedFile.original_name', 'test-image.png');
});

test('upload success state shows preview link', function (): void {
    Storage::fake(config('vault.storage.disk'));

    $upload = UploadedFile::fake()->image('my-photo.jpg', 200, 200);

    livewire(Landing::class)
        ->set('file', $upload)
        ->call('upload')
        ->assertSee('File uploaded')
        ->assertSee('Preview link')
        ->assertSee('Upload another file');
});

test('reset upload returns to form state', function (): void {
    Storage::fake(config('vault.storage.disk'));

    $upload = UploadedFile::fake()->image('reset-test.png', 100, 100);

    livewire(Landing::class)
        ->set('file', $upload)
        ->call('upload')
        ->assertSet('uploadedFile.original_name', 'reset-test.png')
        ->call('resetUpload')
        ->assertSet('uploadedFile', null)
        ->assertSee('Choose a file');
});
