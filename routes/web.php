<?php

use App\Http\Controllers\Accounts\CourseController as AccountsCourseController;
use App\Http\Controllers\Accounts\DashboardController as AccountsDashboardController;
use App\Http\Controllers\Accounts\LegalDocumentController as AccountsLegalDocumentController;
use App\Http\Controllers\Accounts\PayoutController as AccountsPayoutController;
use App\Http\Controllers\Accounts\ScriptController as AccountsScriptController;
use App\Http\Controllers\Accounts\SubscriptionPlanController as AccountsSubscriptionPlanController;
use App\Http\Controllers\Accounts\TransactionController as AccountsTransactionController;
use App\Http\Controllers\Admin\AdvertisementController as AdminAdvertisementController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DemoAccessController as AdminDemoAccessController;
use App\Http\Controllers\Admin\LegalDocumentController as AdminLegalDocumentController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\ScriptController as AdminScriptController;
use App\Http\Controllers\Admin\SubscriptionPlanController as AdminSubscriptionPlanController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Creator\AdvertisementController as CreatorAdvertisementController;
use App\Http\Controllers\Creator\CourseController as CreatorCourseController;
use App\Http\Controllers\Creator\DashboardController as CreatorDashboardController;
use App\Http\Controllers\Creator\EarningsController as CreatorEarningsController;
use App\Http\Controllers\Creator\ScriptController as CreatorScriptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\Monitoring\AdvertisementController as MonitoringAdvertisementController;
use App\Http\Controllers\Monitoring\AuditLogController as MonitoringAuditLogController;
use App\Http\Controllers\Monitoring\CourseController as MonitoringCourseController;
use App\Http\Controllers\Monitoring\DashboardController as MonitoringDashboardController;
use App\Http\Controllers\Monitoring\DemoAccessController as MonitoringDemoAccessController;
use App\Http\Controllers\Monitoring\LegalDocumentController as MonitoringLegalDocumentController;
use App\Http\Controllers\Monitoring\PayoutController as MonitoringPayoutController;
use App\Http\Controllers\Monitoring\ScriptController as MonitoringScriptController;
use App\Http\Controllers\Monitoring\SubscriptionPlanController as MonitoringSubscriptionPlanController;
use App\Http\Controllers\Monitoring\TransactionController as MonitoringTransactionController;
use App\Http\Controllers\Monitoring\UserController as MonitoringUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\User\CourseController as UserCourseController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\DemoAccessController as UserDemoAccessController;
use App\Http\Controllers\User\PurchaseController as UserPurchaseController;
use App\Http\Controllers\User\ScriptController as UserScriptController;
use App\Http\Controllers\User\SubscriptionController as UserSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('legal/{type}', [LegalDocumentController::class, 'show'])->name('legal.show');

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

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
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

        Route::resource('users', AdminUserController::class)->except(['show']);

        Route::resource('advertisements', AdminAdvertisementController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::post('advertisements/{advertisement}/approve', [AdminAdvertisementController::class, 'approve'])->name('advertisements.approve');
        Route::post('advertisements/{advertisement}/reject', [AdminAdvertisementController::class, 'reject'])->name('advertisements.reject');

        Route::resource('demo-access', AdminDemoAccessController::class)->only(['index', 'create', 'store']);
        Route::post('demo-access/{demoAccess}/revoke', [AdminDemoAccessController::class, 'revoke'])->name('demo-access.revoke');

        Route::get('legal-documents', [AdminLegalDocumentController::class, 'index'])->name('legal-documents.index');

        Route::resource('subscription-plans', AdminSubscriptionPlanController::class)->except(['show']);

        // Full per the plan: Admin gets the same reconcile actions Accounts
        // has on Purchases/Orders, but only "View" on Finance & Payouts.
        Route::get('transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
        Route::post('transactions/{transaction}/succeed', [AdminTransactionController::class, 'succeed'])->name('transactions.succeed');
        Route::post('transactions/{transaction}/fail', [AdminTransactionController::class, 'fail'])->name('transactions.fail');

        Route::get('payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    });

Route::middleware(['auth', 'role:monitoring', 'audit'])
    ->prefix('monitoring')
    ->name('monitoring.')
    ->group(function () {
        Route::get('dashboard', [MonitoringDashboardController::class, 'index'])->name('dashboard');

        Route::get('courses', [MonitoringCourseController::class, 'index'])->name('courses.index');
        Route::get('scripts', [MonitoringScriptController::class, 'index'])->name('scripts.index');

        // Full: Advertising is one of Monitoring's exclusive-write domains
        // (alongside Audit and Legal), matching the plan's "Full" cell —
        // not view-only like Admin's "Approve" cell on the same row.
        Route::resource('advertisements', MonitoringAdvertisementController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::post('advertisements/{advertisement}/approve', [MonitoringAdvertisementController::class, 'approve'])->name('advertisements.approve');
        Route::post('advertisements/{advertisement}/reject', [MonitoringAdvertisementController::class, 'reject'])->name('advertisements.reject');

        // View-only, matching the plan's "Monitoring gets view rights equal
        // to Admin across all modules" — these mirror the admin.* views
        // above, not the write actions Admin/Accounts have.
        Route::get('users', [MonitoringUserController::class, 'index'])->name('users.index');
        Route::get('transactions', [MonitoringTransactionController::class, 'index'])->name('transactions.index');
        Route::get('payouts', [MonitoringPayoutController::class, 'index'])->name('payouts.index');
        Route::get('subscription-plans', [MonitoringSubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
        Route::get('demo-access', [MonitoringDemoAccessController::class, 'index'])->name('demo-access.index');
    });

// Audit log viewer, shared by Admin, Monitoring, and SuperAdmin. Already a
// pure read-only page (AuditLog records aren't editable), so no separate
// per-role controller/view needed the way the other domains above have one.
Route::middleware(['auth', 'role:admin,monitoring,superadmin', 'audit'])
    ->prefix('monitoring')
    ->name('monitoring.')
    ->group(function () {
        Route::get('audit-logs', [MonitoringAuditLogController::class, 'index'])->name('audit-logs.index');
    });

// Legal document management, shared by Monitoring and SuperAdmin only —
// Admin gets the separate view-only /admin/legal-documents route instead.
Route::middleware(['auth', 'role:monitoring,superadmin', 'audit'])
    ->prefix('monitoring')
    ->name('monitoring.')
    ->group(function () {
        Route::resource('legal-documents', MonitoringLegalDocumentController::class)->except(['show']);
        Route::post('legal-documents/{legalDocument}/publish', [MonitoringLegalDocumentController::class, 'publish'])->name('legal-documents.publish');
        Route::post('legal-documents/{legalDocument}/unpublish', [MonitoringLegalDocumentController::class, 'unpublish'])->name('legal-documents.unpublish');
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

        Route::resource('advertisements', CreatorAdvertisementController::class)->except(['show']);
        Route::post('advertisements/{advertisement}/submit', [CreatorAdvertisementController::class, 'submit'])->name('advertisements.submit');

        Route::get('earnings', [CreatorEarningsController::class, 'index'])->name('earnings.index');
    });

Route::middleware(['auth', 'role:user'])
    ->prefix('user')
    ->name('user.')
    ->group(function () {
        Route::get('dashboard', [UserDashboardController::class, 'index'])->name('dashboard');

        Route::get('courses', [UserCourseController::class, 'index'])->name('courses.index');
        Route::get('courses/{course}', [UserCourseController::class, 'show'])->name('courses.show');
        Route::post('courses/{course}/purchase', [UserCourseController::class, 'purchase'])->name('courses.purchase');

        Route::get('scripts', [UserScriptController::class, 'index'])->name('scripts.index');
        Route::get('scripts/{script}', [UserScriptController::class, 'show'])->name('scripts.show');
        Route::post('scripts/{script}/purchase', [UserScriptController::class, 'purchase'])->name('scripts.purchase');

        Route::get('purchases', [UserPurchaseController::class, 'index'])->name('purchases.index');

        Route::get('plans', [UserSubscriptionController::class, 'plans'])->name('plans.index');
        Route::post('plans/{plan}/subscribe', [UserSubscriptionController::class, 'subscribe'])->name('plans.subscribe');

        Route::get('subscription', [UserSubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('subscription/cancel', [UserSubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::get('subscription/switch/{plan}', [UserSubscriptionController::class, 'switchPreview'])->name('subscription.switch-preview');
        Route::post('subscription/switch/{plan}', [UserSubscriptionController::class, 'switch'])->name('subscription.switch');

        Route::get('demo-access', [UserDemoAccessController::class, 'index'])->name('demo-access.index');
    });

// Purchase/subscription reconciliation, shared by Accounts and SuperAdmin.
Route::middleware(['auth', 'role:accounts,superadmin', 'audit'])
    ->prefix('accounts')
    ->name('accounts.')
    ->group(function () {
        Route::get('dashboard', [AccountsDashboardController::class, 'index'])->name('dashboard');

        Route::get('transactions', [AccountsTransactionController::class, 'index'])->name('transactions.index');
        Route::post('transactions/{transaction}/succeed', [AccountsTransactionController::class, 'succeed'])->name('transactions.succeed');
        Route::post('transactions/{transaction}/fail', [AccountsTransactionController::class, 'fail'])->name('transactions.fail');

        Route::get('payouts', [AccountsPayoutController::class, 'index'])->name('payouts.index');
        Route::post('payouts', [AccountsPayoutController::class, 'store'])->name('payouts.store');
        Route::post('payouts/{payout}/pay', [AccountsPayoutController::class, 'pay'])->name('payouts.pay');
        Route::post('payouts/{payout}/fail', [AccountsPayoutController::class, 'fail'])->name('payouts.fail');

        Route::get('legal-documents', [AccountsLegalDocumentController::class, 'index'])->name('legal-documents.index');

        // View-only, per the plan's "View only" / "View/manage billing"
        // cells — Accounts sees plan definitions and content but doesn't
        // edit them; billing itself is handled via Purchases/Orders above.
        Route::get('subscription-plans', [AccountsSubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
        Route::get('courses', [AccountsCourseController::class, 'index'])->name('courses.index');
        Route::get('scripts', [AccountsScriptController::class, 'index'])->name('scripts.index');
    });
