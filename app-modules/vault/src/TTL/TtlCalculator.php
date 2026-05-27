<?php

declare(strict_types=1);

namespace He4rt\Vault\TTL;

use DateTimeInterface;
use He4rt\Identity\Users\User;

class TtlCalculator
{
    public function calculate(int $fileSizeBytes, ?User $user = null): int
    {
        $isAuthenticated = $user instanceof User;
        $configKey = $isAuthenticated ? 'vault.ttl.authenticated' : 'vault.ttl.anonymous';

        $minDays = config()->integer($configKey.'.min_days');
        $maxDays = config()->integer($configKey.'.max_days');

        $maxSizeMb = $isAuthenticated
            ? config()->integer('vault.upload.max_size_auth_mb')
            : config()->integer('vault.upload.max_size_mb');

        $maxSizeBytes = $maxSizeMb * 1024 * 1024;
        $ratio = min($fileSizeBytes / $maxSizeBytes, 1.0);

        $ttlDays = $minDays + ($maxDays - $minDays) * ((1 - $ratio) ** 3);

        return max((int) round($ttlDays), $minDays);
    }

    public function calculateExpiresAt(int $fileSizeBytes, ?User $user = null): DateTimeInterface
    {
        return now()->addDays($this->calculate($fileSizeBytes, $user));
    }
}
