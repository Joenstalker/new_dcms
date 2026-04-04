<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->requires_password_change) {
            // Allow access to password update endpoint, logout, and frontend asset requests.
            // Inertia apps typically load the whole layout, so we don't want to block GET requests to Inertia routes.
            // We only block non-GET requests that carry out actions (excluding auth/logout and password updates).

            $route = $request->route() ? $request->route()->getName() : null;

            if ($request->isMethod('GET')) {
                return $next($request);
            }

            if (in_array($route, ['password.update', 'tenant.password.update', 'logout', 'tenant.logout'])) {
                return $next($request);
            }

            // Block action requests (POST/PUT/DELETE) with 403 Forbidden
            return response()->json([
                'message' => 'You must change your password before continuing.'
            ], 403);
        }

        return $next($request);
    }
}
