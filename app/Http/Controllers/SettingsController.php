<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOwnPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * How long, in seconds, a confirmed current password unlocks the new password step.
     */
    public const PASSWORD_CONFIRM_SECONDS = 300;

    /**
     * Session key holding when step 1 succeeded. Cleared on cancel, on success,
     * and by ForgetPasswordChangeConfirmation when the user opens another page.
     */
    public const PASSWORD_CONFIRMED_KEY = 'settings.password_confirmed_at';

    /**
     * Show the signed-in user's own account details and password form.
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['role', 'department']);

        return Inertia::render('settings/edit', [
            // Read-only: admins assign these, users can't change them here.
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name,
                'department' => $user->department?->name,
            ],
            // Only a UI hint for which step to show; updatePassword() checks again.
            'passwordConfirmed' => $this->passwordRecentlyConfirmed($request),
        ]);
    }

    /**
     * Step 1: check the current password before the new password form is shown.
     */
    public function confirmPassword(Request $request): RedirectResponse
    {
        $request->validate(
            ['current_password' => ['required', 'string', 'current_password']],
            ['current_password.current_password' => 'Your current password is incorrect.'],
        );

        $request->session()->put(self::PASSWORD_CONFIRMED_KEY, Date::now()->unix());

        return back();
    }

    /**
     * Cancel at step 2: forget the confirmation so the next attempt starts at step 1.
     */
    public function cancelPasswordChange(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PASSWORD_CONFIRMED_KEY);

        return back();
    }

    /**
     * Step 2: save the new password, but only right after step 1.
     */
    public function updatePassword(UpdateOwnPasswordRequest $request): RedirectResponse
    {
        if (! $this->passwordRecentlyConfirmed($request)) {
            throw ValidationException::withMessages([
                'current_password' => 'Please enter your current password again.',
            ]);
        }

        /** @var User $user */
        $user = $request->user();

        $user->password = $request->validated('password');
        // Sign out "remember me" cookies issued with the old password.
        $user->setRememberToken(Str::random(60));
        $user->save();

        $request->session()->regenerate();
        // The next change needs the (new) current password again.
        $request->session()->forget(self::PASSWORD_CONFIRMED_KEY);

        Inertia::flash('status', 'Your password has been changed.');

        return back();
    }

    private function passwordRecentlyConfirmed(Request $request): bool
    {
        $confirmedAt = $request->session()->get(self::PASSWORD_CONFIRMED_KEY);

        return is_int($confirmedAt)
            && Date::now()->unix() - $confirmedAt < self::PASSWORD_CONFIRM_SECONDS;
    }
}
