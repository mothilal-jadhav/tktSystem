<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserBinController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\Department;
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

        // Any signed-in user (DepartmentPolicy::viewAny).
        Route::get('/departments', [DepartmentController::class, 'index'])
            ->can('viewAny', Department::class)
            ->name('departments.index');

        // Any signed-in user (UserPolicy::viewAny).
        Route::get('/users', [UserController::class, 'index'])
            ->can('viewAny', User::class)
            ->name('users.index');

        // Admins only (UserPolicy::create).
        Route::get('/users/create', [UserController::class, 'create'])
            ->can('create', User::class)
            ->name('users.create');
        Route::post('/users', [UserController::class, 'store'])
            ->can('create', User::class)
            ->name('users.store');

        // Admins only, and never another admin (UserPolicy::update).
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->can('update', 'user')
            ->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->can('update', 'user')
            ->name('users.update');

        // Admins only, and never another admin (UserPolicy::delete).
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->can('delete', 'user')
            ->name('users.destroy');

        // The bin of deleted users. Admins only (UserPolicy::viewTrashed/restore/forceDelete).
        // withTrashed() lets {user} match users that are in the bin.
        Route::get('/bin', [UserBinController::class, 'index'])
            ->can('viewTrashed', User::class)
            ->name('bin.index');
        Route::patch('/bin/{user}/restore', [UserBinController::class, 'restore'])
            ->withTrashed()
            ->can('restore', 'user')
            ->name('bin.restore');
        Route::delete('/bin/{user}', [UserBinController::class, 'destroy'])
            ->withTrashed()
            ->can('forceDelete', 'user')
            ->name('bin.destroy');
    });
});
