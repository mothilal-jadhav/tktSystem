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
}
