<?php

declare(strict_types=1);

namespace He4rt\Security\IpBlocking;

use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

class BlockIpAction
{
    public function handle(string $ip, string $reason, ?DateTimeInterface $blockedUntil = null): BlockedIp
    {
        /** @var BlockedIp $blockedIp */
        $blockedIp = BlockedIp::query()->updateOrCreate(
            ['ip' => $ip],
            ['reason' => $reason, 'blocked_until' => $blockedUntil],
        );

        // Invalidate the cache so the new block takes effect immediately
        Cache::forget('blocked_ip:'.$ip);

        return $blockedIp;
    }
}
