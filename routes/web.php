<?php

use App\Http\Controllers\Accounts\DashboardController as AccountsDashboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Creator\DashboardController as CreatorDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Monitoring\DashboardController as MonitoringDashboardController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    // Sends a signed-in user to the dashboard for their own role.
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth', 'role:monitoring'])
    ->prefix('monitoring')
    ->name('monitoring.')
    ->group(function () {
        Route::get('dashboard', [MonitoringDashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth', 'role:creator'])
    ->prefix('creator')
    ->name('creator.')
    ->group(function () {
        Route::get('dashboard', [CreatorDashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth', 'role:user'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    });

Route::middleware(['auth', 'role:accounts'])
    ->prefix('accounts')
    ->name('accounts.')
    ->group(function () {
        Route::get('dashboard', [AccountsDashboardController::class, 'index'])->name('dashboard');
    });
