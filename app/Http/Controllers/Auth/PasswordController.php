<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the form for replacing a temporary password.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/change-password');
    }

    /**
     * Save the user's own password and lift the forced change.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Only for replacing a temporary password. Everyone else changes their
        // password in Settings, which asks for the current password first.
        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        $user->password = $request->validated('password');
        $user->must_change_password = false;
        // Sign out "remember me" cookies issued with the temporary password.
        $user->setRememberToken(Str::random(60));
        $user->save();

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
