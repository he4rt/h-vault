<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Tests\Feature\Filament\Resources;

use Filament\Facades\Filament;
use He4rt\Identity\Users\User;
use He4rt\PanelApp\Filament\Resources\Buckets\BucketResource;
use He4rt\PanelApp\Filament\Resources\Buckets\Pages\CreateBucket;
use He4rt\PanelApp\Filament\Resources\Buckets\Pages\EditBucket;
use He4rt\PanelApp\Filament\Resources\Buckets\Pages\ListBuckets;
use He4rt\PanelApp\Filament\Resources\Files\Pages\ListFiles;
use He4rt\Vault\Buckets\Bucket;
use He4rt\Vault\Files\File;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
});

// ─── Panel Access ──────────────────────────────────────────────────────────────

it('redirects unauthenticated users to the login page', function (): void {
    get('/app')->assertRedirect('/app/login');
});

it('allows authenticated users to access the app panel', function (): void {
    actingAs(User::factory()->create());

    get('/app')->assertSuccessful();
});

// ─── File Resource ─────────────────────────────────────────────────────────────

it('user can see only their own files', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $ownFiles = File::factory()->count(3)->create(['uploaded_by' => $owner->id]);
    $otherFile = File::factory()->create(['uploaded_by' => $other->id]);

    actingAs($owner);

    livewire(ListFiles::class)
        ->loadTable()
        ->assertCanSeeTableRecords($ownFiles)
        ->assertCanNotSeeTableRecords([$otherFile]);
});

it('user cannot see files uploaded anonymously by others', function (): void {
    $owner = User::factory()->create();

    $anonymousFile = File::factory()->create(['uploaded_by' => null]);

    actingAs($owner);

    livewire(ListFiles::class)
        ->loadTable()
        ->assertCanNotSeeTableRecords([$anonymousFile]);
});

// ─── Bucket Resource ───────────────────────────────────────────────────────────

it('user can see only their own buckets', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $ownBuckets = Bucket::factory()->count(2)->create(['user_id' => $owner->id]);
    $otherBucket = Bucket::factory()->create(['user_id' => $other->id]);

    actingAs($owner);

    livewire(ListBuckets::class)
        ->loadTable()
        ->assertCanSeeTableRecords($ownBuckets)
        ->assertCanNotSeeTableRecords([$otherBucket]);
});

it('can render create bucket page', function (): void {
    actingAs(User::factory()->create());

    get(BucketResource::getUrl('create'))->assertSuccessful();
});

it('can create a bucket scoped to the authenticated user', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    livewire(CreateBucket::class)
        ->fillForm(['name' => 'My New Bucket'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Bucket::query()->where('user_id', $user->id)->where('name', 'My New Bucket')->exists())->toBeTrue();
});

it('can render edit bucket page', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $bucket = Bucket::factory()->create(['user_id' => $user->id]);

    get(BucketResource::getUrl('edit', ['record' => $bucket]))->assertSuccessful();
});

it('can update bucket name', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $bucket = Bucket::factory()->create(['user_id' => $user->id]);

    livewire(EditBucket::class, ['record' => $bucket->getRouteKey()])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($bucket->refresh()->name)->toBe('Updated Name');
});
