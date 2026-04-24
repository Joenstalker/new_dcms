<?php
$tenant = App\Models\Tenant::find('cruzsmile');
$sub = $tenant->subscriptions()->latest()->first();

$customDate = '2026-05-01';
echo "Simulating Admin Update with Custom Expiry: {$customDate}\n";

// Use the Basic plan (ID 1)
$plan = App\Models\SubscriptionPlan::where('name', 'Basic')->first();

$sub->update([
    'subscription_plan_id' => $plan->id,
    'stripe_status' => 'active',
    'payment_status' => 'paid',
    'payment_method' => 'admin_expiry_test',
    'ends_at' => \Illuminate\Support\Carbon::parse($customDate),
]);

$sub->refresh();
echo "\n--- VERIFICATION ---\n";
print_r([
    'plan' => $sub->plan->name,
    'status' => $sub->stripe_status,
    'ends_at' => $sub->ends_at->toDateTimeString(),
    'method' => $sub->payment_method
]);

if ($sub->ends_at->toDateString() === $customDate) {
    echo "\nSUCCESS: Custom expiry date applied correctly!\n";
} else {
    echo "\nFAILURE: Custom expiry date mismatch. Expected {$customDate}, got {$sub->ends_at->toDateString()}\n";
}
