<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTenantFeature
{
    /**
     * Handle an incoming request.
     * Enforce boolean plan features (e.g., sms_notifications, priority_support)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $feature  The feature key to check
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $feature)
    {
        $tenant = tenant();
        
        // Skip check if not in a tenant context
        if (!$tenant) {
            return $next($request);
        }

        if (!$tenant->hasPlanFeature($feature)) {
            $message = "Your current plan does not include access to this feature ({$feature}). Please upgrade your subscription.";
            
            if ($request->wantsJson() || $request->inertia()) {
                abort(403, $message);
            }
            
            return redirect()->route('tenant.billing.index')->with('error', $message);
        }

        return $next($request);
    }
}
