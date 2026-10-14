<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Only admins may create users.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }
}
