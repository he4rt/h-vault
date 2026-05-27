<?php

declare(strict_types=1);

namespace He4rt\Vault\Database\Factories\Buckets;

use He4rt\Identity\Users\User;
use He4rt\Vault\Buckets\Bucket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Bucket>
 */
final class BucketFactory extends Factory
{
    protected $model = Bucket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => Str::uuid()->toString(),
            'share_token' => null,
            'name' => fake()->words(2, true),
        ];
    }

    public function shared(): static
    {
        return $this->state(fn () => [
            'share_token' => Str::uuid()->toString(),
        ]);
    }

    public function unnamed(): static
    {
        return $this->state(fn () => [
            'name' => null,
        ]);
    }
}
