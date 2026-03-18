<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant) {
            // Check tenant's main status field
            $status = $tenant->status ?? 'active';

            if ($status === 'suspended') {
                return response()->make(view('errors.suspended'), 403);
            }

            // If tenant status is pending, show pending approval page
            // Allow access to the pending page so users can see their clinic is being reviewed
            if ($status === 'pending') {
                return response()->make(
                    view('errors.pending', [
                    'created_at' => $tenant->created_at ?? now()->toIso8601String()
                ])->render(),
                    200
                );
            }
        }

        return $next($request);
    }
}
