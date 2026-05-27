<?php

declare(strict_types=1);

namespace He4rt\Admin\Tests\Feature\BlockedIps;

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Admin\Filament\Resources\BlockedIps\BlockedIpResource;
use He4rt\Admin\Filament\Resources\BlockedIps\Pages\CreateBlockedIp;
use He4rt\Admin\Filament\Resources\BlockedIps\Pages\EditBlockedIp;
use He4rt\Admin\Filament\Resources\BlockedIps\Pages\ListBlockedIps;
use He4rt\Identity\Permissions\Roles;
use He4rt\Identity\Users\User;
use He4rt\Security\IpBlocking\BlockedIp;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    actingAs(User::factory()->create());

    artisan('sync:permissions');

    auth()->user()->assignRole(Roles::SuperAdmin->value);
});

it('can list blocked IPs', function (): void {
    $blockedIps = BlockedIp::factory()->count(3)->create();

    livewire(ListBlockedIps::class)
        ->loadTable()
        ->assertCanSeeTableRecords($blockedIps)
        ->assertCountTableRecords(3);
});

it('can render the blocked IP list page', function (): void {
    get(BlockedIpResource::getUrl('index'))
        ->assertSuccessful();
});

it('can render the create blocked IP page', function (): void {
    get(BlockedIpResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create a blocked IP', function (): void {
    $newData = [
        'ip' => '192.168.1.100',
        'reason' => 'Suspicious activity detected.',
        'blocked_until' => now()->addDays(7)->format('Y-m-d H:i:s'),
    ];

    livewire(CreateBlockedIp::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(BlockedIp::class, [
        'ip' => '192.168.1.100',
        'reason' => 'Suspicious activity detected.',
    ]);
});

it('can create a permanent blocked IP with null blocked_until', function (): void {
    $newData = [
        'ip' => '10.0.0.1',
        'reason' => 'Permanent ban.',
        'blocked_until' => null,
    ];

    livewire(CreateBlockedIp::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(BlockedIp::class, [
        'ip' => '10.0.0.1',
        'blocked_until' => null,
    ]);
});

it('can render the edit blocked IP page', function (): void {
    $blockedIp = BlockedIp::factory()->create();

    get(BlockedIpResource::getUrl('edit', ['record' => $blockedIp]))
        ->assertSuccessful();
});

it('can update a blocked IP', function (): void {
    $blockedIp = BlockedIp::factory()->create();

    livewire(EditBlockedIp::class, ['record' => $blockedIp->getRouteKey()])
        ->fillForm([
            'ip' => $blockedIp->ip,
            'reason' => 'Updated reason for the block.',
            'blocked_until' => $blockedIp->blocked_until,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($blockedIp->refresh())
        ->reason->toBe('Updated reason for the block.');
});

it('can delete a blocked IP', function (): void {
    $blockedIp = BlockedIp::factory()->create();

    livewire(ListBlockedIps::class)
        ->loadTable()
        ->callAction(TestAction::make('delete')->table($blockedIp));

    assertDatabaseMissing(BlockedIp::class, ['id' => $blockedIp->id]);
});

it('validates required fields when creating a blocked IP', function (): void {
    livewire(CreateBlockedIp::class)
        ->fillForm([
            'ip' => null,
            'reason' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'ip' => 'required',
            'reason' => 'required',
        ]);
});
