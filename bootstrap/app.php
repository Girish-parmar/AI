<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RecordAuditLog;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'audit' => RecordAuditLog::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        // Opt-in: if Hostinger (or any host) puts a reverse proxy/load
        // balancer in front of the app, set TRUSTED_PROXIES in .env
        // (comma-separated IPs, or "*" to trust the immediate proxy
        // regardless of IP) so HTTPS detection and generated URLs are
        // correct. Left untrusted by default — safe for direct hosting.
        if ($trustedProxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $trustedProxies === '*' ? '*' : explode(',', $trustedProxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
