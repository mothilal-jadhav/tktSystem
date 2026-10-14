<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List users, 15 per page, sorted by name.
     */
    public function index(Request $request): Response
    {
        /** @var User $viewer */
        $viewer = $request->user();

        /** @var LengthAwarePaginator<int, User> $users */
        $users = User::query()->with('role')->orderBy('name')->paginate(15);

        return Inertia::render('users/index', [
            // Admins get the edit/delete column; everyone else sees department and joined date.
            'canManageUsers' => $viewer->hasRole(Role::ADMIN),
            // Only these fields are sent; passwords, tokens and flags never leave the server.
            'users' => $users->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name,
                'joined' => $user->created_at?->toDateString(),
                // Decided by UserPolicy; the UI only uses these to show or hide buttons.
                'can' => [
                    'update' => $viewer->can('update', $user),
                    'delete' => $viewer->can('delete', $user),
                ],
            ]),
        ]);
    }

    /**
     * Show the create user form.
     */
    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => Role::orderBy('id')->pluck('name'),
        ]);
    }

    /**
     * Create a user with a generated email and temporary password.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $name = $request->validated('name');
        $password = Str::password(16);

        $user = new User([
            'name' => $name,
            'email' => $this->uniqueEmailFor($name),
            'password' => $password,
        ]);
        $user->must_change_password = true;
        $user->role()->associate(Role::where('name', $request->validated('role'))->sole());
        $user->save();

        // Flash data is shown on the next page only and is not kept in the
        // browser history, so the temporary password is displayed just once.
        Inertia::flash('createdUser', [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->name,
            'password' => $password,
        ]);

        return redirect()->route('users.create');
    }

    /**
     * Move a user to the bin. They can no longer sign in, but can be restored.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        Inertia::flash('status', "{$user->name} was moved to the bin.");

        return back();
    }

    /**
     * Build an email from the letters in the name, e.g. "Dr. Asha Rao"
     * becomes drasharao@gmail.com, adding a number if it is already taken.
     */
    private function uniqueEmailFor(string $name): string
    {
        $local = strtolower((string) preg_replace('/[^A-Za-z]/', '', $name));

        $email = "{$local}@gmail.com";

        // Users in the bin keep their email, so they are checked too.
        for ($suffix = 1; User::withTrashed()->where('email', $email)->exists(); $suffix++) {
            $email = "{$local}{$suffix}@gmail.com";
        }

        return $email;
    }
}
