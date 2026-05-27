<?php

declare(strict_types=1);

namespace He4rt\Vault\Database\Factories\Files;

use He4rt\Identity\Users\User;
use He4rt\Vault\Enums\FileStatus;
use He4rt\Vault\Files\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
final class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $fileName = fake()->word().'.'.fake()->fileExtension();

        return [
            'token' => Str::uuid()->toString(),
            'uploaded_by' => null,
            'original_name' => $fileName,
            'storage_path' => 'files/'.now()->format('Y/m/').Str::uuid().'.'.(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin'),
            'mime_type' => 'image/png',
            'file_size' => fake()->numberBetween(1024, 10 * 1024 * 1024),
            'password_hash' => null,
            'status' => FileStatus::Pending,
            'thumbnail_path' => null,
            'is_one_time' => false,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function authenticated(): static
    {
        return $this->state(fn () => [
            'uploaded_by' => User::factory(),
        ]);
    }

    public function protected(string $password = 'secret'): static
    {
        return $this->state(fn () => [
            'password_hash' => bcrypt($password),
        ]);
    }

    public function oneTime(): static
    {
        return $this->state(fn () => [
            'is_one_time' => true,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function clean(): static
    {
        return $this->state(fn () => [
            'status' => FileStatus::Clean,
        ]);
    }

    public function withMime(string $mimeType): static
    {
        return $this->state(fn () => [
            'mime_type' => $mimeType,
        ]);
    }
}
