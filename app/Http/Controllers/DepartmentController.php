<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * List every department, sorted by name, with its employee count and manager.
     * Employee names are only shown on the department's own page.
     */
    public function index(): Response
    {
        // Two queries in total: departments with their head counts, then their managers.
        // Users in the bin are left out automatically (SoftDeletes).
        $departments = Department::query()
            ->withCount('users')
            ->with(['users' => fn (HasMany $query) => $query
                ->whereRelation('role', 'name', Role::MANAGER)
                ->orderBy('name')])
            ->orderBy('name')
            ->get();

        return Inertia::render('departments/index', [
            'departments' => $departments->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
                // Everyone in the department, managers included.
                'employeeCount' => $department->getAttribute('users_count'),
                'managers' => $department->users->pluck('name'),
            ]),
        ]);
    }

    /**
     * Show one department: its managers at the top, then everyone else,
     * 15 per page, sorted by name.
     */
    public function show(Department $department): Response
    {
        $managers = $department->users()
            ->whereRelation('role', 'name', Role::MANAGER)
            ->orderBy('name')
            ->get();

        /** @var LengthAwarePaginator<int, User> $employees */
        $employees = $department->users()
            ->whereRelation('role', 'name', '!=', Role::MANAGER)
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('departments/show', [
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
                // Everyone in the department, managers included.
                'employeeCount' => $managers->count() + $employees->total(),
            ],
            'managers' => $managers->map(fn (User $user) => $this->member($user)),
            'employees' => $employees->through(fn (User $user) => $this->member($user)),
        ]);
    }

    /**
     * Only these fields are sent; passwords, tokens and flags never leave the server.
     * The role isn't needed: managers and the team are already shown separately.
     *
     * @return array{id: int, name: string, email: string, joined: string|null}
     */
    private function member(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'joined' => $user->created_at?->toDateString(),
        ];
    }
}
