<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Show the create user form.
     */
    public function create(): Response
    {
        return Inertia::render('users/create');
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
        $user->role()->associate(Role::where('name', Role::REQUESTER)->sole());
        $user->save();

        // Flash data is shown on the next page only and is not kept in the
        // browser history, so the temporary password is displayed just once.
        Inertia::flash('createdUser', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => $password,
        ]);

        return redirect()->route('users.create');
    }

    /**
     * Build an email from the letters in the name, e.g. "Dr. Asha Rao"
     * becomes drasharao@gmail.com, adding a number if it is already taken.
     */
    private function uniqueEmailFor(string $name): string
    {
        $local = strtolower((string) preg_replace('/[^A-Za-z]/', '', $name));

        $email = "{$local}@gmail.com";

        for ($suffix = 1; User::where('email', $email)->exists(); $suffix++) {
            $email = "{$local}{$suffix}@gmail.com";
        }

        return $email;
    }
}
