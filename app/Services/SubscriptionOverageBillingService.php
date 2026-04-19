<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionUsageOverage;
use App\Models\TenantUsageMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SubscriptionOverageBillingService
{
    private const BYTES_PER_MB = 1048576;

    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function applyUpcomingInvoiceOverages(
        Subscription $subscription,
        object $invoice,
        ?string $eventId = null,
        ?string $eventType = null
    ): void {
        if (! config('billing.overage.enabled', true)) {
            return;
        }

        $plan = $subscription->plan;
        $tenant = $subscription->tenant;

        if (! $plan || ! $tenant) {
            return;
        }

        $period = $this->resolveBillingPeriod($invoice);
        if (! $period) {
            Log::warning('Skipping overage billing: invoice period could not be resolved.', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_id,
            ]);

            return;
        }

        [$periodStart, $periodEnd] = $period;

        $tenantId = (string) $subscription->tenant_id;
        $usage = $this->resolveUsageForPeriod($tenantId, $periodStart, $periodEnd, (int) $tenant->storage_used_bytes, (int) $tenant->db_used_bytes);

        $storageIncludedBytes = max(0, ((int) ($plan->max_storage_mb ?? config('billing.overage.default_max_storage_mb', 500))) * self::BYTES_PER_MB);
        $bandwidthIncludedBytes = max(0, ((int) ($plan->max_bandwidth_mb ?? config('billing.overage.default_max_bandwidth_mb', 2048))) * self::BYTES_PER_MB);

        $currency = strtoupper((string) ($invoice->currency ?? 'PHP'));

        $this->upsertMetricInvoiceItem(
            metric: 'storage',
            subscription: $subscription,
            invoice: $invoice,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            includedBytes: $storageIncludedBytes,
            usedBytes: $usage['storage_peak_bytes'],
            unitPricePerMb: (float) ($plan->storage_overage_price_per_gb ?? 0),
            currency: $currency,
            eventId: $eventId,
            eventType: $eventType,
        );

        $this->upsertMetricInvoiceItem(
            metric: 'bandwidth',
            subscription: $subscription,
            invoice: $invoice,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            includedBytes: $bandwidthIncludedBytes,
            usedBytes: $usage['bandwidth_bytes'],
            unitPricePerMb: (float) ($plan->bandwidth_overage_price_per_gb ?? 0),
            unitLabel: 'MB',
            currency: $currency,
            eventId: $eventId,
            eventType: $eventType,
        );

        $this->syncPendingCountOverages(
            subscription: $subscription,
            invoice: $invoice,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            currency: $currency,
            eventId: $eventId,
            eventType: $eventType,
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function resolveBillingPeriod(object $invoice): ?array
    {
        $periodStart = $invoice->period_start ?? null;
        $periodEnd = $invoice->period_end ?? null;

        if (! $periodStart || ! $periodEnd) {
            foreach (($invoice->lines->data ?? []) as $line) {
                $lineStart = $line->period->start ?? null;
                $lineEnd = $line->period->end ?? null;

                if ($lineStart && $lineEnd) {
                    $periodStart = $lineStart;
                    $periodEnd = $lineEnd;
                    break;
                }
            }
        }

        if (! $periodStart || ! $periodEnd) {
            return null;
        }

        $start = Carbon::createFromTimestamp((int) $periodStart, 'UTC');
        $end = Carbon::createFromTimestamp((int) $periodEnd, 'UTC');

        if ($end->lessThanOrEqualTo($start)) {
            return null;
        }

        return [$start, $end];
    }

    /**
     * @return array{bandwidth_bytes: int, storage_peak_bytes: int}
     */
    private function resolveUsageForPeriod(
        string $tenantId,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $fallbackStorageBytes,
        int $fallbackDbBytes
    ): array {
        $startDate = $periodStart->toDateString();
        $endDateExclusive = $periodEnd->toDateString();

        $query = TenantUsageMetric::query()
            ->forTenant($tenantId)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<', $endDateExclusive);

        $bandwidthBytes = (int) (clone $query)->sum('bandwidth_bytes');
        $storagePeakBytes = (int) ((clone $query)->max('total_used_bytes') ?? 0);

        if ($storagePeakBytes <= 0) {
            $storagePeakBytes = max(0, $fallbackStorageBytes + $fallbackDbBytes);
        }

        return [
            'bandwidth_bytes' => max(0, $bandwidthBytes),
            'storage_peak_bytes' => max(0, $storagePeakBytes),
        ];
    }

    private function upsertMetricInvoiceItem(
        string $metric,
        Subscription $subscription,
        object $invoice,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $includedBytes,
        int $usedBytes,
        float $unitPricePerMb,
        string $currency,
        ?string $eventId,
        ?string $eventType,
        string $unitLabel = 'MB'
    ): void {
        $overageBytes = max(0, $usedBytes - $includedBytes);
        $billableMb = $unitLabel === 'unit'
            ? (float) $overageBytes
            : $this->calculateBillableMb($overageBytes);
        $amount = round($billableMb * max(0, $unitPricePerMb), 2);

        $periodStartSql = $periodStart->copy()->utc()->toDateTimeString();
        $periodEndSql = $periodEnd->copy()->utc()->toDateTimeString();

        $entry = SubscriptionUsageOverage::query()->firstOrNew([
            'subscription_id' => $subscription->id,
            'metric' => $metric,
            'billing_period_start' => $periodStartSql,
            'billing_period_end' => $periodEndSql,
        ]);

        // Keep legacy *_gb column names for backward compatibility while storing MB-based values.
        $entry->fill([
            'tenant_id' => (string) $subscription->tenant_id,
            'included_bytes' => $includedBytes,
            'used_bytes' => $usedBytes,
            'overage_bytes' => $overageBytes,
            'billable_quantity_gb' => $billableMb,
            'unit_price_per_gb' => max(0, $unitPricePerMb),
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'stripe_invoice_id' => is_string($invoice->id ?? null) ? $invoice->id : null,
            'stripe_event_id' => $eventId,
            'metadata' => [
                'event_type' => $eventType,
                'period_start' => $periodStart->toIso8601String(),
                'period_end' => $periodEnd->toIso8601String(),
                'stripe_subscription_id' => $subscription->stripe_id,
                'pricing_unit' => $unitLabel,
            ],
        ]);

        if ($overageBytes <= 0 || $unitPricePerMb <= 0 || $amount <= 0) {
            $entry->status = 'skipped';
            $entry->save();

            return;
        }

        $customerId = $this->extractId($invoice->customer ?? null);
        if (! $customerId || ! $subscription->stripe_id) {
            $entry->status = 'failed';
            $entry->save();

            Log::warning('Skipping overage invoice item: missing customer/subscription Stripe IDs.', [
                'subscription_id' => $subscription->id,
                'metric' => $metric,
                'stripe_customer_id' => $customerId,
                'stripe_subscription_id' => $subscription->stripe_id,
            ]);

            return;
        }

        if (config('billing.overage.dry_run', false)) {
            $entry->status = 'dry_run';
            $entry->save();

            Log::info('Overage dry-run: invoice item not created.', [
                'subscription_id' => $subscription->id,
                'metric' => $metric,
                'amount' => $amount,
                'currency' => strtoupper($currency),
                'period_start' => $periodStartSql,
                'period_end' => $periodEndSql,
            ]);

            return;
        }

        $description = sprintf(
            '%s overage: %.4f %s x %.2f %s/%s (%s to %s)',
            ucfirst($metric),
            $billableMb,
            $unitLabel,
            $unitPricePerMb,
            strtoupper($currency),
            $unitLabel,
            $periodStart->toDateString(),
            $periodEnd->toDateString(),
        );

        $payload = [
            'customer' => $customerId,
            'subscription' => $subscription->stripe_id,
            'currency' => strtolower($currency),
            'amount' => (int) round($amount * 100),
            'description' => $description,
            'metadata' => [
                'dcms_metric' => $metric,
                'dcms_tenant_id' => (string) $subscription->tenant_id,
                'dcms_subscription_id' => (string) $subscription->id,
                'dcms_period_start' => $periodStart->toDateString(),
                'dcms_period_end' => $periodEnd->toDateString(),
            ],
        ];

        $invoiceId = is_string($invoice->id ?? null) ? $invoice->id : null;
        if ($invoiceId && str_starts_with($invoiceId, 'in_')) {
            $payload['invoice'] = $invoiceId;
        }

        try {
            if ($entry->stripe_invoice_item_id) {
                $stripeInvoiceItem = $this->stripe->invoiceItems->update($entry->stripe_invoice_item_id, $payload);
            } else {
                $stripeInvoiceItem = $this->stripe->invoiceItems->create($payload);
            }

            $entry->stripe_invoice_item_id = (string) ($stripeInvoiceItem->id ?? $entry->stripe_invoice_item_id);
            $entry->status = 'pending';
            $entry->save();
        } catch (\Throwable $e) {
            $entry->status = 'failed';
            $entry->save();

            Log::error('Failed to create overage invoice item.', [
                'subscription_id' => $subscription->id,
                'metric' => $metric,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function calculateBillableMb(int $bytes): float
    {
        if ($bytes <= 0) {
            return 0.0;
        }

        return ceil(($bytes / self::BYTES_PER_MB) * 10000) / 10000;
    }

    private function syncPendingCountOverages(
        Subscription $subscription,
        object $invoice,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $currency,
        ?string $eventId,
        ?string $eventType
    ): void {
        $periodStartSql = $periodStart->copy()->utc()->toDateTimeString();
        $periodEndSql = $periodEnd->copy()->utc()->toDateTimeString();

        $rows = SubscriptionUsageOverage::query()
            ->where('subscription_id', $subscription->id)
            ->whereIn('metric', ['users', 'patients', 'appointments'])
            ->where('billing_period_start', $periodStartSql)
            ->where('billing_period_end', $periodEndSql)
            ->get();

        foreach ($rows as $row) {
            $this->upsertMetricInvoiceItem(
                metric: (string) $row->metric,
                subscription: $subscription,
                invoice: $invoice,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                includedBytes: (int) $row->included_bytes,
                usedBytes: (int) $row->used_bytes,
                unitPricePerMb: (float) $row->unit_price_per_gb,
                unitLabel: 'unit',
                currency: $currency,
                eventId: $eventId,
                eventType: $eventType,
            );
        }
    }

    private function extractId(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && isset($value->id)) {
            return (string) $value->id;
        }

        return null;
    }
}
