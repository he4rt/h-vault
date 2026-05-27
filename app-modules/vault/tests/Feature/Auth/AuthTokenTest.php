<?php

declare(strict_types=1);

use He4rt\Identity\Users\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('user can generate sanctum token with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson(route('vault.auth.token'), [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'test-device',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token']);
});

test('returns 401 with invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->postJson(route('vault.auth.token'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
        'device_name' => 'test-device',
    ]);

    $response->assertUnauthorized();
});

test('returns validation errors for missing fields', function (): void {
    $response = $this->postJson(route('vault.auth.token'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});
