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

    /**
     * Only admins may open the bin of deleted users.
     */
    public function viewTrashed(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    /**
     * Admins may restore a non-admin user that is in the bin.
     */
    public function restore(User $user, User $target): bool
    {
        return $target->trashed() && $this->delete($user, $target);
    }

    /**
     * Admins may permanently delete a non-admin user that is in the bin.
     */
    public function forceDelete(User $user, User $target): bool
    {
        return $target->trashed() && $this->delete($user, $target);
    }
}
