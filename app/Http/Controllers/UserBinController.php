<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class UserBinController extends Controller
{
    /**
     * List deleted users, most recently deleted first.
     */
    public function index(Request $request): Response
    {
        /** @var User $viewer */
        $viewer = $request->user();

        /** @var LengthAwarePaginator<int, User> $users */
        $users = User::onlyTrashed()->with('role')->latest('deleted_at')->paginate(15);

        return Inertia::render('bin/index', [
            // Only these fields are sent; passwords, tokens and flags never leave the server.
            'users' => $users->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name,
                'deleted' => $user->deleted_at?->toDateString(),
                // Decided by UserPolicy; the UI only uses these to show or hide buttons.
                'can' => [
                    'restore' => $viewer->can('restore', $user),
                    'forceDelete' => $viewer->can('forceDelete', $user),
                ],
            ]),
        ]);
    }

    /**
     * Bring a user back from the bin.
     */
    public function restore(User $user): RedirectResponse
    {
        $user->restore();

        Inertia::flash('status', "{$user->name} was restored.");

        return back();
    }

    /**
     * Permanently delete a user. This cannot be undone.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->forceDelete();

        Inertia::flash('status', "{$user->name} was permanently deleted.");

        return back();
    }
}
