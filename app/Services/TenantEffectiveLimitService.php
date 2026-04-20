<?php

namespace App\Services;

use App\Models\Subscription;

class TenantEffectiveLimitService
{
    /**
     * Metrics that can scale during prepaid multi-month windows.
     * Users stay fixed by policy.
     *
     * @var array<int, string>
     */
    private const SCALABLE_METRICS = [
        'appointments',
        'storage_mb',
        'bandwidth_mb',
    ];

    public function getMultiplier(?Subscription $subscription): int
    {
        if (! $this->isPrepaidBoostActive($subscription)) {
            return 1;
        }

        return max(1, (int) ($subscription->limit_multiplier ?? 1));
    }

    public function isPrepaidBoostActive(?Subscription $subscription): bool
    {
        if (! $subscription) {
            return false;
        }

        $multiplier = (int) ($subscription->limit_multiplier ?? 1);
        if ($multiplier <= 1) {
            return false;
        }

        if (! $subscription->prepaid_started_at || ! $subscription->prepaid_ends_at) {
            return false;
        }

        $now = now();

        return $now->greaterThanOrEqualTo($subscription->prepaid_started_at)
            && $now->lessThanOrEqualTo($subscription->prepaid_ends_at);
    }

    public function resolveEffectiveLimit(?Subscription $subscription, string $metric, mixed $baseLimit): mixed
    {
        if ($baseLimit === null || $baseLimit === '' || (int) $baseLimit === -1) {
            return $baseLimit;
        }

        $base = (int) $baseLimit;
        if ($base <= 0) {
            return $base;
        }

        if (! in_array($metric, self::SCALABLE_METRICS, true)) {
            return $base;
        }

        return $base * $this->getMultiplier($subscription);
    }

    /**
     * @return array{active: bool, multiplier: int, starts_at: string|null, ends_at: string|null, months: int}
     */
    public function getPrepaidContext(?Subscription $subscription): array
    {
        $multiplier = $subscription ? max(1, (int) ($subscription->limit_multiplier ?? 1)) : 1;
        $startsAt = $subscription && $subscription->prepaid_started_at
            ? $subscription->prepaid_started_at->toIso8601String()
            : null;
        $endsAt = $subscription && $subscription->prepaid_ends_at
            ? $subscription->prepaid_ends_at->toIso8601String()
            : null;
        $months = $subscription ? max(1, (int) ($subscription->prepaid_months ?? 1)) : 1;

        return [
            'active' => $this->isPrepaidBoostActive($subscription),
            'multiplier' => $multiplier,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'months' => $months,
        ];
    }
}
