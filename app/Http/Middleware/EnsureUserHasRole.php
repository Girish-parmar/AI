<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Abort with 403 unless the authenticated user holds one of the given roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // tryFrom, not from: a typo'd role string in a route definition
        // should deny access (null never matches $user->role), not 500.
        $allowed = array_filter(array_map(fn (string $role) => Role::tryFrom($role), $roles));

        abort_unless($user && in_array($user->role, $allowed, true), 403);

        return $next($request);
    }
}
