<?php

declare(strict_types=1);

namespace He4rt\Admin\Tests\Feature\Files;

use Filament\Facades\Filament;
use He4rt\Admin\Filament\Resources\Files\FileResource;
use He4rt\Admin\Filament\Resources\Files\Pages\ListFiles;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    actingAs(User::factory()->create());

    artisan('sync:permissions');

    auth()->user()->assignRole(Roles::SuperAdmin->value);
});

it('can list all files', function (): void {
    $files = File::factory()->count(5)->create();

    livewire(ListFiles::class)
        ->loadTable()
        ->assertCanSeeTableRecords($files)
        ->assertCountTableRecords(5);
});

it('can render the file list page', function (): void {
    get(FileResource::getUrl('index'))
        ->assertSuccessful();
});

it('shows files from all users, not just the authenticated user', function (): void {
    $otherUser = User::factory()->create();
    $myFile = File::factory()->for(auth()->user(), 'uploader')->create();
    $otherFile = File::factory()->for($otherUser, 'uploader')->create();
    $anonymousFile = File::factory()->create();

    livewire(ListFiles::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$myFile, $otherFile, $anonymousFile]);
});

it('can search files by original name', function (): void {
    $targetFile = File::factory()->create(['original_name' => 'unique-target-file.png']);
    $otherFile = File::factory()->create(['original_name' => 'some-other-file.jpg']);

    livewire(ListFiles::class)
        ->loadTable()
        ->searchTable('unique-target-file')
        ->assertCanSeeTableRecords([$targetFile])
        ->assertCanNotSeeTableRecords([$otherFile]);
});

it('can filter files by status', function (): void {
    $cleanFile = File::factory()->create(['status' => FileStatus::Clean]);
    $infectedFile = File::factory()->create(['status' => FileStatus::Infected]);

    livewire(ListFiles::class)
        ->loadTable()
        ->filterTable('status', FileStatus::Clean->value)
        ->assertCanSeeTableRecords([$cleanFile])
        ->assertCanNotSeeTableRecords([$infectedFile]);
});
