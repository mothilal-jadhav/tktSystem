<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Every signed-in user may see the user list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only admins may create users.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    /**
     * Admins may edit any user except another admin (or themselves).
     */
    public function update(User $user, User $target): bool
    {
        return $user->hasRole(Role::ADMIN) && ! $target->hasRole(Role::ADMIN);
    }

    /**
     * Admins may delete any user except another admin (or themselves).
     */
    public function delete(User $user, User $target): bool
    {
        return $user->hasRole(Role::ADMIN) && ! $target->hasRole(Role::ADMIN);
    }
}
