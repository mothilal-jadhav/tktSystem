<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// TODO: add ->middleware('auth') once the login feature exists.
Route::get('/', DashboardController::class)->name('dashboard');
