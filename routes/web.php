<?php

use App\Http\Controllers\Accounts\DashboardController as AccountsDashboardController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ScriptController as AdminScriptController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Creator\CourseController as CreatorCourseController;
use App\Http\Controllers\Creator\DashboardController as CreatorDashboardController;
use App\Http\Controllers\Creator\ScriptController as CreatorScriptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Monitoring\AuditLogController as MonitoringAuditLogController;
use App\Http\Controllers\Monitoring\CourseController as MonitoringCourseController;
use App\Http\Controllers\Monitoring\DashboardController as MonitoringDashboardController;
use App\Http\Controllers\Monitoring\ScriptController as MonitoringScriptController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\User\CourseController as UserCourseController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\ScriptController as UserScriptController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        // Laravel's throttle key is IP-based only (not route-based) unless a
        // prefix is given, so each guest POST endpoint needs its own prefix
        // or they'd all silently share one combined rate-limit bucket.
        ->middleware('throttle:5,1,register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1,login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1,forgot-password')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1,reset-password')
        ->name('password.update');
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

// Full course/script management, shared by Admin and SuperAdmin (SuperAdmin's
// sidebar links into these same /admin/* pages rather than duplicating them).
Route::middleware(['auth', 'role:admin,superadmin', 'audit'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('courses', AdminCourseController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::post('courses/{course}/approve', [AdminCourseController::class, 'approve'])->name('courses.approve');
        Route::post('courses/{course}/reject', [AdminCourseController::class, 'reject'])->name('courses.reject');

        Route::resource('scripts', AdminScriptController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::post('scripts/{script}/approve', [AdminScriptController::class, 'approve'])->name('scripts.approve');
        Route::post('scripts/{script}/reject', [AdminScriptController::class, 'reject'])->name('scripts.reject');
    });

Route::middleware(['auth', 'role:monitoring', 'audit'])
    ->prefix('monitoring')
    ->name('monitoring.')
    ->group(function () {
        Route::get('dashboard', [MonitoringDashboardController::class, 'index'])->name('dashboard');

        Route::get('courses', [MonitoringCourseController::class, 'index'])->name('courses.index');
        Route::get('scripts', [MonitoringScriptController::class, 'index'])->name('scripts.index');
    });

// Audit log viewer, shared by Monitoring and SuperAdmin.
Route::middleware(['auth', 'role:monitoring,superadmin', 'audit'])
    ->prefix('monitoring')
    ->name('monitoring.')
    ->group(function () {
        Route::get('audit-logs', [MonitoringAuditLogController::class, 'index'])->name('audit-logs.index');
    });

Route::middleware(['auth', 'role:creator'])
    ->prefix('creator')
    ->name('creator.')
    ->group(function () {
        Route::get('dashboard', [CreatorDashboardController::class, 'index'])->name('dashboard');

        Route::resource('courses', CreatorCourseController::class)->except(['show']);
        Route::post('courses/{course}/submit', [CreatorCourseController::class, 'submit'])->name('courses.submit');

        Route::resource('scripts', CreatorScriptController::class)->except(['show']);
        Route::post('scripts/{script}/submit', [CreatorScriptController::class, 'submit'])->name('scripts.submit');
    });

Route::middleware(['auth', 'role:user'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('dashboard', [UserDashboardController::class, 'index'])->name('dashboard');

        Route::get('courses', [UserCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/{course}', [UserCourseController::class, 'show'])->name('courses.show');

        Route::get('scripts', [UserScriptController::class, 'index'])->name('scripts.index');
        Route::get('scripts/{script}', [UserScriptController::class, 'show'])->name('scripts.show');
    });

Route::middleware(['auth', 'role:accounts', 'audit'])
    ->prefix('accounts')
    ->name('accounts.')
    ->group(function () {
        Route::get('dashboard', [AccountsDashboardController::class, 'index'])->name('dashboard');
    });
