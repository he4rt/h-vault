<?php

declare(strict_types=1);

namespace He4rt\Vault\Albums;

use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AlbumPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Album $album): bool
    {
        return $user->id === $album->bucket->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Album $album): bool
    {
        return $user->id === $album->bucket->user_id;
    }

    public function delete(User $user, Album $album): bool
    {
        return $user->id === $album->bucket->user_id;
    }
}
