<?php

declare(strict_types=1);

namespace He4rt\Security\IpBlocking;

use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlockedIpPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('security.blocked_ips.view');
    }

    public function view(User $user, BlockedIp $blockedIp): bool
    {
        return $user->hasPermissionTo('security.blocked_ips.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('security.blocked_ips.create');
    }

    public function update(User $user, BlockedIp $blockedIp): bool
    {
        return $user->hasPermissionTo('security.blocked_ips.update');
    }

    public function delete(User $user, BlockedIp $blockedIp): bool
    {
        return $user->hasPermissionTo('security.blocked_ips.delete');
    }
}
