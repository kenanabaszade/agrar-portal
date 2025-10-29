<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     * This middleware attempts to authenticate the user if a token is provided,
     * but does not fail if no token or invalid token is provided.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to authenticate using Sanctum if token is provided
        if ($token = $request->bearerToken()) {
            try {
                // Authenticate using Sanctum guard
                $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                
                if ($user && $user->tokenable) {
                    // Set the authenticated user
                    auth()->setUser($user->tokenable);
                }
            } catch (\Exception $e) {
                // If authentication fails, continue without authentication
            }
        }
        
        return $next($request);
    }
}
