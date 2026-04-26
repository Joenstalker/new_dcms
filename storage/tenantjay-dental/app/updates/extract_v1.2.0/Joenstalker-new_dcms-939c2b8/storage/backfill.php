<?php
foreach (App\Models\PendingRegistration::all() as $pr) {
    App\Models\SystemEarning::create([
        'tenant_id' => $pr->subdomain,
        'amount' => $pr->amount_paid ?: 0,
        'currency' => 'PHP',
        'stripe_payment_intent_id' => $pr->stripe_payment_intent_id,
        'stripe_session_id' => $pr->stripe_session_id,
        'description' => 'Tenant Registration Payment (Historical Backfill)',
        'paid_at' => $pr->created_at,
    ]);
}
echo "Backfilled matching payments.\n";
