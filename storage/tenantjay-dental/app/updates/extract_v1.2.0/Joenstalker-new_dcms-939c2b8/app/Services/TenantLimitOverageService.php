<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionUsageOverage;
use App\Models\TenantLimitOverageConsent;
use Carbon\Carbon;

class TenantLimitOverageService
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveBillingWindow(Subscription $subscription): array
    {
        $periodEnd = $subscription->billing_cycle_end
            ?? $subscription->ends_at
            ?? now()->addMonth();

        $periodEnd = $periodEnd->copy()->utc();
        $periodStart = ($subscription->billing_cycle === 'yearly')
            ? $periodEnd->copy()->subYear()
            : $periodEnd->copy()->subMonth();

        return [$periodStart, $periodEnd];
    }

    public function hasConsent(Subscription $subscription, string $metric): bool
    {
        [$periodStart, $periodEnd] = $this->resolveBillingWindow($subscription);

        return TenantLimitOverageConsent::query()
            ->where('tenant_id', (string) $subscription->tenant_id)
            ->where('subscription_id', $subscription->id)
            ->where('metric', $metric)
            ->where('billing_period_start', $periodStart->toDateTimeString())
            ->where('billing_period_end', $periodEnd->toDateTimeString())
            ->where('status', 'accepted')
            ->exists();
    }

    public function grantConsent(Subscription $subscription, string $metric, ?int $actorId = null): TenantLimitOverageConsent
    {
        [$periodStart, $periodEnd] = $this->resolveBillingWindow($subscription);

        $consent = TenantLimitOverageConsent::query()->firstOrNew([
            'tenant_id' => (string) $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'metric' => $metric,
            'billing_period_start' => $periodStart->toDateTimeString(),
            'billing_period_end' => $periodEnd->toDateTimeString(),
        ]);

        $consent->status = 'accepted';
        $consent->accepted_at = now()->utc();
        $consent->metadata = array_merge((array) $consent->metadata, [
            'accepted_by_user_id' => $actorId,
        ]);
        $consent->save();

        return $consent;
    }

    /**
     * @return array{metric:string,current:int,limit:int,over_by:int,unit_price:float,projected_amount:float,currency:string}
     */
    public function quoteCountMetric(string $metric, int $current, int $limit): array
    {
        $price = $this->getCountMetricPrice($metric);
        $overBy = max(0, $current + 1 - $limit);

        return [
            'metric' => $metric,
            'current' => $current,
            'limit' => $limit,
            'over_by' => $overBy,
            'unit_price' => $price,
            'projected_amount' => round($overBy * $price, 2),
            'currency' => 'PHP',
        ];
    }

    public function registerCountOverageAttempt(Subscription $subscription, string $metric, int $limit, int $current): void
    {
        [$periodStart, $periodEnd] = $this->resolveBillingWindow($subscription);

        $periodStartSql = $periodStart->toDateTimeString();
        $periodEndSql = $periodEnd->toDateTimeString();

        $entry = SubscriptionUsageOverage::query()->firstOrNew([
            'subscription_id' => $subscription->id,
            'metric' => $metric,
            'billing_period_start' => $periodStartSql,
            'billing_period_end' => $periodEndSql,
        ]);

        $usedUnits = max($current + 1, (int) ($entry->used_bytes ?? 0));
        $overageUnits = max(0, $usedUnits - $limit);
        $unitPrice = $this->getCountMetricPrice($metric);

        $entry->fill([
            'tenant_id' => (string) $subscription->tenant_id,
            'included_bytes' => max(0, $limit),
            'used_bytes' => max(0, $usedUnits),
            'overage_bytes' => max(0, $overageUnits),
            'billable_quantity_gb' => max(0, $overageUnits),
            'unit_price_per_gb' => max(0, $unitPrice),
            'amount' => round($overageUnits * $unitPrice, 2),
            'currency' => 'PHP',
            'status' => $overageUnits > 0 && $unitPrice > 0 ? 'pending' : 'skipped',
            'metadata' => array_merge((array) $entry->metadata, [
                'pricing_unit' => 'unit',
                'metric_kind' => 'count',
            ]),
        ]);

        $entry->save();
    }

    public function getCountMetricPrice(string $metric): float
    {
        return (float) match ($metric) {
            'users' => config('billing.overage.count_prices.users', 0),
            'patients' => config('billing.overage.count_prices.patients', 0),
            'appointments' => config('billing.overage.count_prices.appointments', 0),
            default => 0,
        };
    }
}
