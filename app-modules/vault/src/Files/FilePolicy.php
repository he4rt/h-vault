<?php

declare(strict_types=1);

namespace He4rt\Vault\Files;

use He4rt\Identity\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, File $file): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(?User $user, File $file): bool
    {
        if ($file->uploaded_by === null) {
            return false;
        }

        return $user instanceof User && $user->id === $file->uploaded_by;
    }

    public function delete(?User $user, File $file): bool
    {
        if ($file->uploaded_by === null) {
            return true;
        }

        return $user instanceof User && $user->id === $file->uploaded_by;
    }
}
