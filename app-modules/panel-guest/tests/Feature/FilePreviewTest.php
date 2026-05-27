<?php

declare(strict_types=1);

use He4rt\PanelGuest\Livewire\FilePreview;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

uses(LazilyRefreshDatabase::class);

test('preview page renders for existing file', function (): void {
    $file = File::factory()->create(['original_name' => 'hello.png']);

    $response = $this->get(route('guest.file-preview', [
        'token' => $file->token,
        'filename' => $file->original_name,
    ]));

    $response->assertOk();
    $response->assertSeeLivewire(FilePreview::class);
});

test('preview page shows file info for public file', function (): void {
    $file = File::factory()->create([
        'original_name' => 'document.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 2 * 1024 * 1024,
    ]);

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->assertSee('document.pdf')
        ->assertSee('application/pdf')
        ->assertSee('Download')
        ->assertSet('unlocked', true)
        ->assertSet('notFound', false)
        ->assertSet('expired', false);
});

test('preview page shows not found for non-existent token', function (): void {
    $fakeUuid = Str::uuid()->toString();

    livewire(FilePreview::class, [
        'token' => $fakeUuid,
        'filename' => 'ghost.txt',
    ])
        ->assertSet('notFound', true)
        ->assertSee('File not found');
});

test('preview page shows not found for soft-deleted file', function (): void {
    $file = File::factory()->create();
    $file->delete();

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->assertSet('notFound', true)
        ->assertSee('File not found');
});

test('preview page shows expired message for expired file', function (): void {
    $file = File::factory()->expired()->create();

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->assertSet('expired', true)
        ->assertSee('File expired');
});

test('preview page shows password form for protected file', function (): void {
    $file = File::factory()->protected('mysecret')->create();

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->assertSet('unlocked', false)
        ->assertSee('Password required');
});

test('correct password unlocks protected file', function (): void {
    $file = File::factory()->protected('mysecret')->create();

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->set('password', 'mysecret')
        ->call('unlock')
        ->assertSet('unlocked', true)
        ->assertSet('error', null)
        ->assertSee($file->original_name)
        ->assertSee('Download');
});

test('incorrect password shows error for protected file', function (): void {
    $file = File::factory()->protected('mysecret')->create();

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->set('password', 'wrongpassword')
        ->call('unlock')
        ->assertSet('unlocked', false)
        ->assertSet('error', 'Incorrect password.');
});

test('preview type is detected from mime type', function (string $mime, string $expected): void {
    $file = File::factory()->withMime($mime)->create();

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->assertSet('previewType', $expected);
})->with([
    ['image/png', 'image'],
    ['image/jpeg', 'image'],
    ['video/mp4', 'video'],
    ['audio/mpeg', 'audio'],
    ['application/pdf', 'pdf'],
    ['application/octet-stream', 'generic'],
    ['text/plain', 'generic'],
]);

test('formatted size displays correctly', function (): void {
    $file = File::factory()->create(['file_size' => 5 * 1024 * 1024]);

    livewire(FilePreview::class, [
        'token' => $file->token,
        'filename' => $file->original_name,
    ])
        ->assertSee('5 MB');
});
