<?php

namespace App\Services;

use App\Models\PaymentHistory;
use App\Models\PendingRegistration;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantPaymentHistory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentHistoryService
{
    public function recordRegistrationSuccess(PendingRegistration $registration, object $session, ?Subscription $subscription = null): PaymentHistory
    {
        $paymentIntentId = $this->extractId($session->payment_intent ?? null);
        $currency = strtoupper((string) ($session->currency ?? 'PHP'));
        $amount = (float) (($session->amount_total ?? 0) / 100);
        $existingCode = PaymentHistory::query()
            ->where('stripe_session_id', $session->id)
            ->where('transaction_type', 'registration')
            ->value('transaction_code');

        $billedAddress = collect([
            $registration->street,
            $registration->barangay,
            $registration->city,
            $registration->province,
            $registration->region,
        ])->filter()->implode(', ');

        $entry = PaymentHistory::query()->updateOrCreate(
            [
                'stripe_session_id' => $session->id,
                'transaction_type' => 'registration',
            ],
            [
                'tenant_id' => $subscription?->tenant_id,
                'subscription_id' => $subscription?->id,
                'pending_registration_id' => $registration->id,
                'transaction_code' => $existingCode ?: $this->buildTransactionCode(),
                'status' => 'success',
                'plan_name' => $subscription?->plan?->name,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_label' => $this->formatPaymentMethodLabel($session),
                'payment_method_brand' => $this->extractPaymentMethodBrand($session),
                'payment_method_last4' => $this->extractPaymentMethodLast4($session),
                'description' => sprintf('Initial registration payment for %s plan', $subscription?->plan->name ?? 'DCMS'),
                'billed_to_name' => trim($registration->first_name.' '.$registration->last_name),
                'billed_to_email' => $registration->email,
                'billed_to_address' => $billedAddress ?: null,
                'stripe_event_type' => 'checkout.session.completed',
                'stripe_payment_intent_id' => $paymentIntentId,
                'metadata' => [
                    'billing_cycle' => $registration->billing_cycle,
                    'months' => $registration->subscription_months ?? 1,
                    'clinic_name' => $registration->clinic_name,
                ],
                'paid_at' => now('UTC'),
            ]
        );

        $this->mirrorToTenantDatabase($entry);

        return $entry;
    }

    public function recordInvoiceEvent(
        Subscription $subscription,
        object $invoice,
        string $status,
        ?string $eventId = null,
        ?string $eventType = null,
        ?string $transactionType = null
    ): PaymentHistory {
        $amountPaid = (float) (($invoice->amount_paid ?? $invoice->amount_due ?? 0) / 100);
        $currency = strtoupper((string) ($invoice->currency ?? 'PHP'));
        $existingCode = PaymentHistory::query()
            ->where('stripe_invoice_id', $invoice->id)
            ->value('transaction_code');
        $tenantAddress = collect([
            $subscription->tenant?->street,
            $subscription->tenant?->barangay,
            $subscription->tenant?->city,
            $subscription->tenant?->province,
            $subscription->tenant?->region,
        ])->filter()->implode(', ');

        $transactionType = $transactionType ?: $this->inferTransactionTypeFromInvoice($invoice);
        $paidAt = isset($invoice->status_transitions->paid_at) && $invoice->status_transitions->paid_at
            ? Carbon::createFromTimestamp($invoice->status_transitions->paid_at)
            : now('UTC');

        $entry = PaymentHistory::query()->updateOrCreate(
            ['stripe_invoice_id' => $invoice->id],
            [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'transaction_code' => $existingCode ?: $this->buildTransactionCode(),
                'status' => $status,
                'transaction_type' => $transactionType,
                'plan_name' => $subscription->plan?->name,
                'amount' => $amountPaid,
                'currency' => $currency,
                'payment_method_label' => $this->formatPaymentMethodLabel($invoice),
                'payment_method_brand' => $this->extractPaymentMethodBrand($invoice),
                'payment_method_last4' => $this->extractPaymentMethodLast4($invoice),
                'description' => $this->describeTransaction($transactionType, $subscription),
                'billed_to_name' => $subscription->tenant?->owner_name,
                'billed_to_email' => $subscription->tenant?->email,
                'billed_to_address' => $tenantAddress ?: null,
                'stripe_event_id' => $eventId,
                'stripe_event_type' => $eventType,
                'stripe_payment_intent_id' => $this->extractId($invoice->payment_intent ?? null),
                'stripe_charge_id' => $this->extractId($invoice->charge ?? null),
                'metadata' => [
                    'billing_reason' => $invoice->billing_reason ?? null,
                    'invoice_number' => $invoice->number ?? null,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url ?? null,
                ],
                'paid_at' => $status === 'success' ? $paidAt : now('UTC'),
            ]
        );

        $this->mirrorToTenantDatabase($entry);

        return $entry;
    }

    public function recordRefund(
        Subscription $subscription,
        object $refund,
        ?string $eventId = null,
        ?string $eventType = null
    ): PaymentHistory {
        $amount = (float) (($refund->amount ?? 0) / 100);
        $currency = strtoupper((string) ($refund->currency ?? 'PHP'));
        $existingCode = PaymentHistory::query()
            ->where('stripe_refund_id', $refund->id)
            ->value('transaction_code');
        $tenantAddress = collect([
            $subscription->tenant?->street,
            $subscription->tenant?->barangay,
            $subscription->tenant?->city,
            $subscription->tenant?->province,
            $subscription->tenant?->region,
        ])->filter()->implode(', ');

        $entry = PaymentHistory::query()->updateOrCreate(
            ['stripe_refund_id' => $refund->id],
            [
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'transaction_code' => $existingCode ?: $this->buildTransactionCode(),
                'status' => 'refund',
                'transaction_type' => 'refund',
                'plan_name' => $subscription->plan?->name,
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_label' => $this->formatPaymentMethodLabel($refund),
                'payment_method_brand' => $this->extractPaymentMethodBrand($refund),
                'payment_method_last4' => $this->extractPaymentMethodLast4($refund),
                'description' => 'Refund issued to tenant',
                'billed_to_name' => $subscription->tenant?->owner_name,
                'billed_to_email' => $subscription->tenant?->email,
                'billed_to_address' => $tenantAddress ?: null,
                'stripe_event_id' => $eventId,
                'stripe_event_type' => $eventType,
                'stripe_charge_id' => $this->extractId($refund->charge ?? null),
                'metadata' => [
                    'reason' => $refund->reason ?? null,
                    'status' => $refund->status ?? null,
                ],
                'paid_at' => now('UTC'),
            ]
        );

        $this->mirrorToTenantDatabase($entry);

        return $entry;
    }

    public function inferTransactionTypeFromInvoice(object $invoice): string
    {
        $reason = (string) ($invoice->billing_reason ?? '');

        if ($reason === 'subscription_cycle') {
            return 'renewal';
        }

        if (in_array($reason, ['subscription_update', 'upcoming'], true)) {
            return 'adjustment';
        }

        if ($reason === 'subscription_create') {
            return 'registration';
        }

        return 'adjustment';
    }

    public function describeTransaction(string $transactionType, Subscription $subscription): string
    {
        $planName = optional($subscription->plan)->name ?? 'plan';

        return match ($transactionType) {
            'registration' => sprintf('Initial subscription payment for %s', $planName),
            'renewal' => sprintf('Subscription renewal for %s', $planName),
            'upgrade' => sprintf('Plan upgrade payment for %s', $planName),
            'downgrade' => sprintf('Plan downgrade adjustment for %s', $planName),
            'refund' => 'Refund transaction',
            default => 'Subscription billing adjustment',
        };
    }

    private function buildTransactionCode(): string
    {
        do {
            $code = Str::upper(Str::random(7));
        } while (PaymentHistory::query()->where('transaction_code', $code)->exists());

        return $code;
    }

    private function formatPaymentMethodLabel(object $source): string
    {
        $brand = $this->extractPaymentMethodBrand($source);
        $last4 = $this->extractPaymentMethodLast4($source);

        if (! $last4) {
            return 'Card payment';
        }

        $brand = $brand ? Str::title($brand) : 'Card';

        return sprintf('%s ending in %s', $brand, $last4);
    }

    private function extractPaymentMethodBrand(object $source): ?string
    {
        $sourceArray = $this->toArray($source);

        $brand = Arr::get($sourceArray, 'payment_method_details.card.brand')
            ?? Arr::get($sourceArray, 'charge.payment_method_details.card.brand')
            ?? Arr::get($sourceArray, 'default_payment_method.card.brand')
            ?? Arr::get($sourceArray, 'payment_intent.payment_method_details.card.brand');

        return $brand ? (string) $brand : null;
    }

    private function extractPaymentMethodLast4(object $source): ?string
    {
        $sourceArray = $this->toArray($source);

        $last4 = Arr::get($sourceArray, 'payment_method_details.card.last4')
            ?? Arr::get($sourceArray, 'charge.payment_method_details.card.last4')
            ?? Arr::get($sourceArray, 'default_payment_method.card.last4')
            ?? Arr::get($sourceArray, 'payment_intent.payment_method_details.card.last4');

        return $last4 ? (string) $last4 : null;
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

    private function toArray(object $value): array
    {
        return json_decode(json_encode($value), true) ?: [];
    }

    private function mirrorToTenantDatabase(PaymentHistory $entry): void
    {
        if (! $entry->tenant_id) {
            return;
        }

        $tenant = Tenant::find($entry->tenant_id);
        if (! $tenant) {
            return;
        }

        try {
            tenancy()->initialize($tenant);

            if (! Schema::hasTable('payment_histories')) {
                tenancy()->end();

                return;
            }

            TenantPaymentHistory::query()->updateOrCreate(
                ['central_history_id' => $entry->id],
                [
                    'transaction_code' => $entry->transaction_code,
                    'status' => $entry->status,
                    'transaction_type' => $entry->transaction_type,
                    'plan_name' => $entry->plan_name,
                    'amount' => $entry->amount,
                    'currency' => $entry->currency,
                    'payment_method_label' => $entry->payment_method_label,
                    'description' => $entry->description,
                    'billed_to_name' => $entry->billed_to_name,
                    'billed_to_email' => $entry->billed_to_email,
                    'billed_to_address' => $entry->billed_to_address,
                    'stripe_payment_intent_id' => $entry->stripe_payment_intent_id,
                    'stripe_invoice_id' => $entry->stripe_invoice_id,
                    'stripe_charge_id' => $entry->stripe_charge_id,
                    'stripe_refund_id' => $entry->stripe_refund_id,
                    'metadata' => $entry->metadata,
                    'paid_at' => $entry->paid_at,
                ]
            );

            tenancy()->end();
        } catch (\Throwable $e) {
            Log::warning('Payment history tenant mirror sync failed: '.$e->getMessage(), [
                'tenant_id' => $entry->tenant_id,
                'payment_history_id' => $entry->id,
            ]);

            if (function_exists('tenancy')) {
                try {
                    tenancy()->end();
                } catch (\Throwable) {
                }
            }
        }
    }
}
