<?php

namespace App\Policies;

use App\Models\User;

class DepartmentPolicy
{
    /**
     * Every signed-in user may see the department list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }
}
