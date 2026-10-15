<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the form that asks for the account's email address.
     */
    public function create(): Response
    {
        return Inertia::render('auth/forgot-password');
    }

    /**
     * Email a reset link if an account exists for the address.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        // Same message whether or not the account exists (or was throttled),
        // so the form can't be used to find out which emails are registered.
        Inertia::flash('status', 'If an account exists for that email, a password reset link has been sent to it.');

        return back();
    }
}
