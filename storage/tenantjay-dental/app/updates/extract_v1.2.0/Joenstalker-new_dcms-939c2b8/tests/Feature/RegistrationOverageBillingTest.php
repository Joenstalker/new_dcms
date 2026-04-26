<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionUsageOverage;
use App\Models\Tenant;
use App\Models\TenantUsageMetric;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationOverageBillingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function invoice_upcoming_webhook_creates_dry_run_overage_rows_and_is_idempotent_per_period_metric(): void
    {
        config([
            'services.stripe.webhook_secret' => 'whsec_test_secret',
            'billing.overage.enabled' => true,
            'billing.overage.dry_run' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Overage Plan',
            'price_monthly' => 1000,
            'price_yearly' => 10000,
            'max_users' => 5,
            'max_storage_mb' => 100,
            'max_bandwidth_mb' => 100,
            'storage_overage_price_per_gb' => 50,
            'bandwidth_overage_price_per_gb' => 40,
            'stripe_monthly_price_id' => 'price_overage_monthly',
        ]);

        $tenantId = 'tenant-overage-'.uniqid();
        $tenant = Tenant::withoutEvents(function () use ($tenantId) {
            return Tenant::create([
                'id' => $tenantId,
                'name' => 'Overage Tenant',
                'email' => 'owner@overage.test',
            ]);
        });

        $subscription = Subscription::create([
            'tenant_id' => (string) $tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_id' => 'sub_overage_123',
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
        ]);

        $periodStart = Carbon::parse('2026-04-01 00:00:00', 'UTC');
        $periodEnd = Carbon::parse('2026-05-01 00:00:00', 'UTC');

        $mb = 1024 * 1024;

        TenantUsageMetric::create([
            'tenant_id' => (string) $tenant->id,
            'date' => '2026-04-05',
            'bandwidth_bytes' => 130 * $mb,
            'request_count' => 10,
            'api_request_count' => 4,
            'public_request_count' => 6,
            'file_used_bytes' => 0,
            'db_used_bytes' => 0,
            'total_used_bytes' => 150 * $mb,
        ]);

        TenantUsageMetric::create([
            'tenant_id' => (string) $tenant->id,
            'date' => '2026-04-10',
            'bandwidth_bytes' => 140 * $mb,
            'request_count' => 12,
            'api_request_count' => 6,
            'public_request_count' => 6,
            'file_used_bytes' => 0,
            'db_used_bytes' => 0,
            'total_used_bytes' => 260 * $mb,
        ]);

        $firstPayload = $this->buildStripeEventPayload('evt_overage_1', [
            'id' => 'in_upcoming_overage_1',
            'object' => 'invoice',
            'subscription' => $subscription->stripe_id,
            'customer' => 'cus_overage_123',
            'currency' => 'php',
            'period_start' => $periodStart->timestamp,
            'period_end' => $periodEnd->timestamp,
            'lines' => [
                'data' => [
                    [
                        'period' => [
                            'start' => $periodStart->timestamp,
                            'end' => $periodEnd->timestamp,
                        ],
                    ],
                ],
            ],
        ]);

        $firstResponse = $this->postStripeWebhook($firstPayload);
        $firstResponse->assertStatus(200)->assertJson(['success' => true]);

        $secondPayload = $this->buildStripeEventPayload('evt_overage_2', [
            'id' => 'in_upcoming_overage_1',
            'object' => 'invoice',
            'subscription' => $subscription->stripe_id,
            'customer' => 'cus_overage_123',
            'currency' => 'php',
            'period_start' => $periodStart->timestamp,
            'period_end' => $periodEnd->timestamp,
            'lines' => [
                'data' => [
                    [
                        'period' => [
                            'start' => $periodStart->timestamp,
                            'end' => $periodEnd->timestamp,
                        ],
                    ],
                ],
            ],
        ]);

        $secondResponse = $this->postStripeWebhook($secondPayload);
        $secondResponse->assertStatus(200)->assertJson(['success' => true]);

        $rows = SubscriptionUsageOverage::query()
            ->where('subscription_id', $subscription->id)
            ->orderBy('metric')
            ->get();

        $this->assertCount(2, $rows, 'Expected exactly two overage rows (storage and bandwidth).');

        $storage = $rows->firstWhere('metric', 'storage');
        $bandwidth = $rows->firstWhere('metric', 'bandwidth');

        $this->assertNotNull($storage);
        $this->assertNotNull($bandwidth);

        $this->assertSame('dry_run', $storage->status);
        $this->assertSame('dry_run', $bandwidth->status);

        $this->assertGreaterThan(0, (int) $storage->overage_bytes);
        $this->assertGreaterThan(0, (int) $bandwidth->overage_bytes);

        $this->assertGreaterThan(0, (float) $storage->amount);
        $this->assertGreaterThan(0, (float) $bandwidth->amount);

        $this->assertNull($storage->stripe_invoice_item_id);
        $this->assertNull($bandwidth->stripe_invoice_item_id);
    }

    /**
     * @param  array<string, mixed>  $invoiceObject
     * @return string
     */
    private function buildStripeEventPayload(string $eventId, array $invoiceObject): string
    {
        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => 'invoice.upcoming',
            'data' => [
                'object' => $invoiceObject,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function postStripeWebhook(string $payload)
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test_secret');
        $header = "t={$timestamp},v1={$signature}";

        return $this->call('POST', '/registration/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $header,
        ], $payload);
    }
}
