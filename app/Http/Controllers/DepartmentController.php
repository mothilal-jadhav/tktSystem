<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * List every department, sorted by name, with its employees.
     */
    public function index(): Response
    {
        // Two queries in total: one for departments and one for all their users.
        // Users in the bin are left out automatically (SoftDeletes).
        $departments = Department::query()
            ->with(['users' => fn (HasMany $query) => $query->with('role')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('departments/index', [
            // Only these fields are sent to the browser; emails and passwords are not.
            'departments' => $departments->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'employees' => $department->users->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role->name,
                ]),
            ]),
        ]);
    }
}
