<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\PaymentHistoryService;
use Illuminate\Console\Command;

class BackfillTenantPaymentHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-history:backfill-tenant
                            {tenant_id : Tenant identifier (e.g. gab-smile)}
                            {--type=registration : Transaction type (registration|renewal|upgrade|downgrade|adjustment)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill payment history for a tenant subscription and mirror it into tenant DB';

    /**
     * Execute the console command.
     */
    public function handle(PaymentHistoryService $paymentHistoryService): int
    {
        $tenantId = (string) $this->argument('tenant_id');
        $type = (string) $this->option('type');

        $subscription = Subscription::with(['plan', 'tenant'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->first();

        if (! $subscription) {
            $this->error("No subscription found for tenant [{$tenantId}].");

            return self::FAILURE;
        }

        $invoiceId = 'backfill_'.$tenantId.'_'.$subscription->id;

        $invoice = (object) [
            'id' => $invoiceId,
            'amount_paid' => (int) round(((float) $subscription->stripe_price) * 100),
            'amount_due' => (int) round(((float) $subscription->stripe_price) * 100),
            'currency' => 'php',
            'billing_reason' => 'subscription_create',
            'number' => 'BF-'.$subscription->id,
            'hosted_invoice_url' => null,
            'payment_intent' => null,
            'charge' => null,
            'status_transitions' => (object) [
                'paid_at' => optional($subscription->created_at)->timestamp ?? now()->timestamp,
            ],
        ];

        $entry = $paymentHistoryService->recordInvoiceEvent(
            $subscription,
            $invoice,
            'success',
            null,
            'backfill.manual',
            $type
        );

        $this->info('Payment history backfilled successfully.');
        $this->line('Tenant: '.$tenantId);
        $this->line('Central record ID: '.$entry->id);
        $this->line('Transaction code: '.$entry->transaction_code);
        $this->line('Plan: '.($entry->plan_name ?? 'N/A'));
        $this->line('Amount: '.number_format((float) $entry->amount, 2));

        return self::SUCCESS;
    }
}
