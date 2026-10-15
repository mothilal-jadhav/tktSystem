<?php

namespace App\Http\Middleware;

use App\Http\Controllers\SettingsController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForgetPasswordChangeConfirmation
{
    /**
     * Opening any page other than Settings ends a password change in progress,
     * so returning to Settings starts again at step 1 (current password).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')
            && ! $request->routeIs('settings.edit')
            // Inertia prefetches links on hover; that isn't leaving the page.
            && $request->header('Purpose') !== 'prefetch'
            && $request->hasSession()) {
            $request->session()->forget(SettingsController::PASSWORD_CONFIRMED_KEY);
        }

        return $next($request);
    }
}
