<?php

declare(strict_types=1);

namespace He4rt\Vault\Database\Factories\Albums;

use He4rt\Vault\Albums\Album;
use He4rt\Vault\Buckets\Bucket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Album>
 */
final class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        return [
            'bucket_id' => Bucket::factory(),
            'name' => fake()->words(2, true),
            'is_shared' => false,
            'share_token' => null,
        ];
    }

    public function shared(): static
    {
        return $this->state(fn () => [
            'is_shared' => true,
            'share_token' => Str::uuid()->toString(),
        ]);
    }
}
