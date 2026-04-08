<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeatureUpdate;

class TenantFeatureGateService
{
    /**
     * Evaluate tenant access to a feature key using entitlement + optional applied update + optional min version.
     */
    public function evaluate(string $tenantId, string|array $featureKeys, bool $requireApplied = true, ?string $minReleaseVersion = null): array
    {
        $normalizedKeys = $this->normalizeFeatureKeys($featureKeys);

        if ($this->isPreviewTenant($tenantId)) {
            return [
                'allowed' => true,
                'reason' => 'preview_tenant',
                'feature' => null,
            ];
        }

        $feature = Feature::whereIn('key', $normalizedKeys)->first();
        if (!$feature) {
            return [
                'allowed' => false,
                'reason' => 'feature_missing',
                'feature' => null,
            ];
        }

        $subscription = Subscription::where('tenant_id', $tenantId)
            ->where('stripe_status', 'active')
            ->where(function ($query) {
                $query->whereNull('billing_cycle_end')
                    ->orWhere('billing_cycle_end', '>', now());
            })
            ->with('plan.features')
            ->latest()
            ->first();

        if (!$subscription || !$subscription->plan) {
            return [
                'allowed' => false,
                'reason' => 'subscription_required',
                'feature' => $feature,
            ];
        }

        $hasEntitlement = collect($normalizedKeys)->contains(fn(string $key) => $subscription->plan->hasFeature($key));
        if (!$hasEntitlement) {
            return [
                'allowed' => false,
                'reason' => 'plan_missing_feature',
                'feature' => $feature,
            ];
        }

        if ($requireApplied) {
            $featureIds = Feature::whereIn('key', $normalizedKeys)->pluck('id');
            $hasApplied = TenantFeatureUpdate::where('tenant_id', $tenantId)
                ->whereIn('feature_id', $featureIds)
                ->where('status', TenantFeatureUpdate::STATUS_APPLIED)
                ->exists();

            if (!$hasApplied) {
                return [
                    'allowed' => false,
                    'reason' => 'update_not_applied',
                    'feature' => $feature,
                ];
            }
        }

        if ($minReleaseVersion !== null) {
            $tenant = Tenant::find($tenantId);
            $tenantVersion = ltrim((string)($tenant?->version ?: 'v1.0.0'), 'vV');
            $required = ltrim($minReleaseVersion, 'vV');

            if (version_compare($tenantVersion, $required, '<')) {
                return [
                    'allowed' => false,
                    'reason' => 'min_release_version_not_met',
                    'feature' => $feature,
                ];
            }
        }

        return [
            'allowed' => true,
            'reason' => 'allowed',
            'feature' => $feature,
        ];
    }

    /**
     * Normalize aliases while transitioning keys.
     */
    public function normalizeFeatureKeys(string|array $featureKeys): array
    {
        $keys = is_array($featureKeys) ? $featureKeys : [$featureKeys];

        $normalized = [];
        foreach ($keys as $key) {
            if ($key === 'security_settings' || $key === 'configuration_settings') {
                $normalized[] = 'security_settings';
                $normalized[] = 'configuration_settings';
                continue;
            }

            $normalized[] = $key;
        }

        return array_values(array_unique($normalized));
    }

    public function isPreviewTenant(string $tenantId): bool
    {
        $previewTenantId = (string)config('tenancy.preview.tenant_id', 'preview-sandbox');
        return $tenantId === $previewTenantId;
    }
}
