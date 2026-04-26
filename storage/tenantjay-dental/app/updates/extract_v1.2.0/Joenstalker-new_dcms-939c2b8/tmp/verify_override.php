<?php
$tenant = App\Models\Tenant::find('cruzsmile');
$sub = $tenant->subscriptions()->latest()->first();
echo "--- BEFORE OVERRIDE ---\n";
print_r([
    'plan' => $sub->plan->name,
    'status' => $sub->stripe_status,
    'payment' => $sub->payment_status,
    'method' => $sub->payment_method
]);

// Simulate Admin Update (Pro Plan is ID 2 usually, Basic is 1)
$newPlan = App\Models\SubscriptionPlan::where('name', 'Pro')->first();
if ($sub->subscription_plan_id != $newPlan->id) {
    echo "\nUpdating to Pro Plan...\n";
    $sub->update([
        'subscription_plan_id' => $newPlan->id,
        'stripe_status' => 'active',
        'payment_status' => 'paid',
        'payment_method' => 'admin_override',
        'ends_at' => now()->addYears(10),
    ]);
    
    $otaService = app(App\Services\FeatureOTAUpdateService::class);
    $otaService->pushTenantPlanUpdates($tenant, $newPlan);
} else {
    echo "\nAlready on Pro Plan, simulating update anyway...\n";
    $sub->update([
        'stripe_status' => 'active',
        'payment_status' => 'paid',
        'payment_method' => 'admin_override_test',
    ]);
}

$sub->refresh();
echo "\n--- AFTER OVERRIDE ---\n";
print_r([
    'plan' => $sub->plan->name,
    'status' => $sub->stripe_status,
    'payment' => $sub->payment_status,
    'method' => $sub->payment_method,
    'ends_at' => $sub->ends_at->toDateTimeString()
]);

// Check for jobs
$jobCount = DB::table('jobs')->count();
echo "\nJobs in queue: {$jobCount}\n";
