<?php

declare(strict_types=1);

namespace He4rt\Security\Database\Factories\BlockedIps;

use He4rt\Security\IpBlocking\BlockedIp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedIp>
 */
final class BlockedIpFactory extends Factory
{
    protected $model = BlockedIp::class;

    public function definition(): array
    {
        return [
            'ip' => fake()->ipv4(),
            'reason' => fake()->sentence(),
            'blocked_until' => now()->addDays(7),
        ];
    }

    public function permanent(): static
    {
        return $this->state(fn () => [
            'blocked_until' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'blocked_until' => now()->subDay(),
        ]);
    }

    public function ipv6(): static
    {
        return $this->state(fn () => [
            'ip' => fake()->ipv6(),
        ]);
    }
}
