<?php

namespace App\Policies;

use App\Models\Department;
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

    /**
     * Every signed-in user may see a department and its employees.
     */
    public function view(User $user, Department $department): bool
    {
        return true;
    }
}
