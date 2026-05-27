<?php

declare(strict_types=1);

namespace He4rt\Vault\Buckets;

use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BucketPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bucket $bucket): bool
    {
        return $user->id === $bucket->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Bucket $bucket): bool
    {
        return $user->id === $bucket->user_id;
    }

    public function delete(User $user, Bucket $bucket): bool
    {
        return $user->id === $bucket->user_id;
    }
}
