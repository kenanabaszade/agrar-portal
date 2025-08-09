<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        // Accept match on user_type or attached roles
        $userRoleTypes = array_map('strtolower', $roles);

        if (in_array(strtolower((string) $user->user_type), $userRoleTypes, true)) {
            return $next($request);
        }

        if ($user->roles()->whereIn('name', $roles)->exists()) {
            return $next($request);
        }

        abort(403, 'Forbidden');
    }
}


