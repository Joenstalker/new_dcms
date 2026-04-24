<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Feature;
use Symfony\Component\HttpFoundation\Response;

class FeatureGate
{
    /**
     * Enforces strict feature lifecycles and subscription plan barriers.
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $tenant = tenant();
        if (!$tenant) {
            abort(403, 'A valid clinic context is required to access this feature.');
        }

        $feature = Feature::where('key', $featureKey)->first();

        if (!$feature) {
            abort(404, 'Feature configuration not found on global registry.');
        }

        // 1. Lifecycle Checks
        if ($feature->implementation_status === Feature::STATUS_DEPRECATED) {
            abort(403, 'This feature has been deprecated from the platform.');
        }

        if ($feature->implementation_status === Feature::STATUS_COMING_SOON) {
            // It might be visible in the UI via Inertia props, but explicitly unusable here
            abort(403, 'This feature is coming soon and is currently unusable.');
        }

        // Maintenance status is handled by the layout, so we allow it to proceed
        if ($feature->implementation_status === Feature::STATUS_MAINTENANCE) {
            return $next($request);
        }

        // 2. Beta Closed Testing Override Check
        $tenantOverride = $tenant->tenantFeatures()->where('feature_id', $feature->id)->first();

        if ($feature->implementation_status === Feature::STATUS_BETA) {
            if (!$tenantOverride || !$tenantOverride->is_enabled) {
                // If they lack a specific beta override enabled, refuse access
                abort(403, 'This feature is currently in closed beta. Beta access required.');
            }
            // Beta testers bypass regular subscription locks for this specific beta
            return $next($request);
        }

        // 3. Implied Subscription Scope Validation
        $hasAccess = false;

        // Tenant overrides take highest precedence 
        if ($tenantOverride && $tenantOverride->is_enabled) {
            $hasAccess = true;
        }
        else {
            if ($tenant->subscription && $tenant->subscription->plan) {
                $hasAccess = $feature->isEnabledForPlan($tenant->subscription->plan);
            }
        }

        if (!$hasAccess) {
            abort(403, 'Your current subscription plan does not support this feature. Please upgrade.');
        }

        return $next($request);
    }
}
