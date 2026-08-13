<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The app uses Bootstrap 5, not Tailwind (Laravel's default).
        Paginator::useBootstrapFive();

        // The dashboard layout (sidebar, page title) and the modules card
        // (rendered inside each dashboard page's own section, a separate
        // view scope from the layout) both need the signed-in user's role,
        // so controllers don't each need to remember to pass it in.
        View::composer(['layouts.dashboard', 'partials.dashboard-modules-card'], function ($view): void {
            if (! $view->offsetExists('role') && auth()->check()) {
                $view->with('role', auth()->user()->role);
            }
        });
    }
}
