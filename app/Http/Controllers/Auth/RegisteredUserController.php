<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Create the account, sign the new user in, and send them to the dashboard.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // The password is hashed by the model's `hashed` cast.
        $user = User::create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        // A new session ID prevents session fixation.
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
