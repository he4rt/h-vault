<?php

declare(strict_types=1);

namespace He4rt\Security\IpBlocking;

use App\Models\BaseModel;
use He4rt\Security\Database\Factories\BlockedIps\BlockedIpFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Support\Carbon;

/**
 * @property-read string $id
 * @property string $ip
 * @property string $reason
 * @property Carbon|null $blocked_until
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 */
#[UseFactory(BlockedIpFactory::class)]
final class BlockedIp extends BaseModel
{
    protected $table = 'security_blocked_ips';

    public function isExpired(): bool
    {
        return $this->blocked_until !== null && $this->blocked_until->isPast();
    }

    public function isPermanent(): bool
    {
        return $this->blocked_until === null;
    }

    protected function casts(): array
    {
        return [
            'blocked_until' => 'datetime',
        ];
    }
}
