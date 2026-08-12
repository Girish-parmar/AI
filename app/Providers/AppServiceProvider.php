<?php

namespace App\Providers;

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
        // The dashboard layout (sidebar, page title) always reflects the
        // signed-in user's own role, so controllers don't each need to
        // remember to pass it in.
        View::composer('layouts.dashboard', function ($view): void {
            if (! $view->offsetExists('role') && auth()->check()) {
                $view->with('role', auth()->user()->role);
            }
        });
    }
}
