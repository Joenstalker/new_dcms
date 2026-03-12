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
            $status = $tenant->subscription_status ?? 'active';

            if ($status === 'suspended') {
                return response()->make(view('errors.suspended'), 403);
            }
            
            // If pending payment, we could redirect to a billing page, but for now we'll allow access 
            // with maybe a banner, or block them too. Let's block them for now.
            if ($status === 'pending_payment') {
                return response()->make(view('errors.pending'), 403);
            }
        }

        return $next($request);
    }
}
