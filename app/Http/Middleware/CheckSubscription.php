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

        if ($this->isDedicatedPreviewTenant((string)$tenant->getTenantKey())) {
            return $next($request);
        }

        if ($this->isTenantPreviewActive($request, (string)$tenant->getTenantKey())) {
            return $next($request);
        }

        // Load the active subscription with its plan
        $subscription = Subscription::where('tenant_id', $tenant->getTenantKey())
            ->where('stripe_status', 'active')
            ->where(function ($query) {
            // If billing_cycle_end is set, it must be in the future
            $query->whereNull('billing_cycle_end')
                ->orWhere('billing_cycle_end', '>', now());
        })
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
        $tenantId = $tenant->getTenantKey();

        // If a specific feature or limit was requested, check it
        if ($feature) {
            $featureCandidates = $this->resolveFeatureCandidates($feature);

            // 1. Check for numeric limit first (e.g. max_users)
            $limitChecks = [
                'max_patients' => fn() => \App\Models\Patient::count(),
                'max_users' => fn() => \App\Models\User::count(),
                'max_appointments' => fn() => \App\Models\Appointment::count(),
            ];

            if (isset($limitChecks[$feature])) {
                $max = $plan->getFeatureValue($feature);

                // null/empty means unlimited
                if ($max !== null && $max !== '') {
                    $current = $limitChecks[$feature]();
                    if ($current >= (int)$max) {
                        return $this->limitReached($request, $feature, (int)$max, $plan->name);
                    }
                }
            }
            // 2. Check for boolean feature (qr_booking, has_sms, etc.)
            else {
                $enabledFeatureKey = collect($featureCandidates)->first(fn(string $candidate) => $plan->hasFeature($candidate));

                if (!$enabledFeatureKey) {
                    $featureModel = collect($featureCandidates)
                        ->map(fn(string $candidate) => $plan->getFeature($candidate))
                        ->first();

                    if (!$featureModel || $featureModel->implementation_status !== \App\Models\Feature::STATUS_MAINTENANCE) {
                        return $this->featureNotAvailable($request, $feature, $plan->name);
                    }
                }

                // Acknowledgment logic for OTA updates (only for dynamic features)
                $dynamicFeature = collect($featureCandidates)
                    ->map(fn(string $candidate) => $plan->getFeature($candidate))
                    ->first();

                if ($dynamicFeature && $dynamicFeature->requiresAcknowledgment()) {
                    $tenantUpdate = TenantFeatureUpdate::where('tenant_id', $tenantId)
                        ->where('feature_id', $dynamicFeature->id)
                        ->first();

                    if (!$tenantUpdate || !$tenantUpdate->isApplied()) {
                        Log::info("CheckSubscription: feature '{$feature}' available but not acknowledged by tenant.");
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
                'has_qr_booking' => $plan->hasFeature('qr_booking'),
                'has_sms' => $plan->hasFeature('sms_notifications'),
                'has_branding' => $plan->hasFeature('custom_branding'),
                'has_analytics' => $plan->hasFeature('advanced_analytics'),
                'has_priority_support' => $plan->hasFeature('priority_support'),
                'has_multi_branch' => $plan->hasFeature('multi_branch'),
                'max_users' => $plan->getFeatureValue('max_users'),
                'max_patients' => $plan->getFeatureValue('max_patients'),
                'max_appointments' => $plan->getFeatureValue('max_appointments'),
                'report_level' => $plan->getFeatureValue('report_level'),
                'max_storage_mb' => $plan->getFeatureValue('max_storage_mb'),
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

    /**
     * Return true only when a valid preview session is active for this tenant.
     */
    private function isTenantPreviewActive(Request $request, string $tenantId): bool
    {
        $preview = $request->session()->get('tenant_preview_active');

        return is_array($preview)
            && ($preview['active'] ?? false) === true
            && (string)($preview['tenant_id'] ?? '') === $tenantId;
    }

    /**
     * Keep compatibility while transitioning feature keys.
     */
    private function resolveFeatureCandidates(string $feature): array
    {
        if ($feature === 'configuration_settings') {
            return ['security_settings', 'configuration_settings'];
        }

        if ($feature === 'security_settings') {
            return ['security_settings', 'configuration_settings'];
        }

        return [$feature];
    }

    /**
     * Dedicated preview tenant should never be plan-gated.
     */
    private function isDedicatedPreviewTenant(string $tenantId): bool
    {
        $previewTenantId = (string)config('tenancy.preview.tenant_id', 'preview-sandbox');
        return $tenantId === $previewTenantId;
    }
}
