<?php

namespace App\Http\Middleware;

use App\Models\Feature;
use App\Models\Subscription;
use App\Models\TenantFeatureUpdate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckSubscription Middleware
 *
 * Enforces subscription plan limits and feature flags for tenant routes.
 *
 * Usage in routes:
 *   ->middleware('check.subscription')                  // just checks active subscription
 *   ->middleware('check.subscription:has_sms')          // checks a feature flag
 *   ->middleware('check.subscription:max_patients')     // checks a numeric limit
 *
 * Supported feature flags (boolean columns on subscription_plans):
 *   has_qr_booking, has_sms, has_branding, has_analytics,
 *   has_priority_support, has_multi_branch
 *
 * Supported limit checks (integer columns on subscription_plans):
 *   max_users, max_patients, max_appointments
 */
class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  string|null  $feature  Optional feature flag or limit key to check.
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $tenant = tenant();

        // If not in a tenant context, pass through
        if (!$tenant) {
            return $next($request);
        }

        // Load the active subscription with its plan
        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        // No active subscription — block access
        if (!$subscription || !$subscription->plan) {
            Log::warning('CheckSubscription: no active subscription found for tenant.', [
                'tenant_id' => $tenant->getTenantKey(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'subscription_required',
                    'message' => 'An active subscription is required to access this feature.',
                ], 403);
            }

            return redirect()->route('settings.index')
                ->with('error', 'Your subscription is inactive. Please renew to continue.');
        }

        $plan = $subscription->plan;

        // If a specific feature or limit was requested, check it
        if ($feature) {
            // -------------------------------------------------------
            // Boolean feature flag check
            // -------------------------------------------------------
            $booleanFeatures = [
                'has_qr_booking',
                'has_sms',
                'has_branding',
                'has_analytics',
                'has_priority_support',
                'has_multi_branch',
            ];

            if (in_array($feature, $booleanFeatures)) {
                if (!$plan->{ $feature}) {
                    return $this->featureNotAvailable($request, $feature, $plan->name);
                }
            }

            // -------------------------------------------------------
            // Dynamic feature check with OTA status
            // -------------------------------------------------------
            $dynamicFeature = Feature::where('key', $feature)->first();
            if ($dynamicFeature) {
                $isEnabled = $dynamicFeature->isEnabledForPlan($plan);

                if ($isEnabled) {
                    // Check if feature requires tenant acknowledgment
                    if ($dynamicFeature->requiresAcknowledgment()) {
                        $tenantUpdate = TenantFeatureUpdate::where('tenant_id', $tenant->getTenantKey())
                            ->where('feature_id', $dynamicFeature->id)
                            ->first();

                        // If feature requires acknowledgment and tenant hasn't applied it
                        if (!$tenantUpdate || $tenantUpdate->status !== TenantFeatureUpdate::STATUS_APPLIED) {
                            // For now, we allow access but could block if needed
                            // The frontend will show "Coming Soon" badge
                            Log::info("CheckSubscription: feature '{$feature}' available but not acknowledged by tenant.");
                        }
                    }
                }
                else {
                    return $this->featureNotAvailable($request, $feature, $plan->name);
                }
            }

            // -------------------------------------------------------
            // Numeric limit check — checks current count vs. plan max
            // -------------------------------------------------------
            $limitChecks = [
                'max_patients' => fn() => \App\Models\Patient::count(),
                'max_users' => fn() => \App\Models\User::count(),
                'max_appointments' => fn() => \App\Models\Appointment::count(),
            ];

            if (isset($limitChecks[$feature])) {
                $max = $plan->{ $feature};

                // null means unlimited
                if ($max !== null) {
                    $current = $limitChecks[$feature]();

                    if ($current >= $max) {
                        return $this->limitReached($request, $feature, $max, $plan->name);
                    }
                }
            }
        }

        // Share plan info with Inertia so the frontend can conditionally show/hide features
        if (class_exists(\Inertia\Inertia::class)) {
            // Get dynamic features with their status
            $features = $plan->features()->ordered()->get();
            $featureStatus = [];

            foreach ($features as $feature) {
                $updateStatus = null;
                $isApplied = false;

                // Check if tenant has acknowledged this feature
                $tenantUpdate = TenantFeatureUpdate::where('tenant_id', $tenant->getTenantKey())
                    ->where('feature_id', $feature->id)
                    ->first();

                if ($tenantUpdate) {
                    $updateStatus = $tenantUpdate->status;
                    $isApplied = $tenantUpdate->isApplied();
                }

                $featureStatus[$feature->key] = [
                    'implementation_status' => $feature->implementation_status,
                    'update_status' => $updateStatus,
                    'is_applied' => $isApplied,
                    'is_enabled' => $feature->isEnabledForPlan($plan),
                    'requires_acknowledgment' => $feature->requiresAcknowledgment(),
                ];
            }

            \Inertia\Inertia::share('subscription', [
                'plan_name' => $plan->name,
                'has_qr_booking' => $plan->has_qr_booking,
                'has_sms' => $plan->has_sms,
                'has_branding' => $plan->has_branding,
                'has_analytics' => $plan->has_analytics,
                'has_priority_support' => $plan->has_priority_support,
                'has_multi_branch' => $plan->has_multi_branch,
                'max_users' => $plan->max_users,
                'max_patients' => $plan->max_patients,
                'max_appointments' => $plan->max_appointments,
                'report_level' => $plan->report_level,
                'billing_cycle' => $subscription->billing_cycle,
                'stripe_status' => $subscription->stripe_status,
                'features' => $featureStatus,
            ]);
        }

        return $next($request);
    }

    /**
     * Return a "feature not available on your plan" response.
     */
    private function featureNotAvailable(Request $request, string $feature, string $planName): Response
    {
        $label = str_replace(['has_', '_'], ['', ' '], $feature);

        Log::info("CheckSubscription: feature '{$feature}' not available on plan '{$planName}'.");

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'feature_not_available',
                'message' => "The '{$label}' feature is not available on your current plan ({$planName}). Please upgrade to access this feature.",
            ], 403);
        }

        return back()->with('error', "The '{$label}' feature is not available on your current plan ({$planName}). Please upgrade to access this feature.");
    }

    /**
     * Return a "plan limit reached" response.
     */
    private function limitReached(Request $request, string $limit, int $max, string $planName): Response
    {
        $label = str_replace('max_', '', $limit);

        Log::info("CheckSubscription: limit '{$limit}' ({$max}) reached on plan '{$planName}'.");

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'limit_reached',
                'message' => "You have reached the maximum number of {$label} ({$max}) allowed on your current plan ({$planName}). Please upgrade to add more.",
            ], 403);
        }

        return back()->with('error', "You have reached the maximum number of {$label} ({$max}) allowed on your current plan ({$planName}). Please upgrade to add more.");
    }
}
