<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    // Reachable while a temporary password still has to be changed.
    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // POST (not GET) so the CSRF check stops other sites from signing users out.
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware(EnsurePasswordIsChanged::class)->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        // Admins only (UserPolicy::create).
        Route::get('/users/create', [UserController::class, 'create'])
            ->can('create', User::class)
            ->name('users.create');
        Route::post('/users', [UserController::class, 'store'])
            ->can('create', User::class)
            ->name('users.store');
    });
});
