<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Feature;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = Tenant::first();
if (!$tenant || !$tenant->subscription || !$tenant->subscription->plan) {
    echo "Tenant or Plan not found\n";
    exit;
}
$plan = $tenant->subscription->plan;
echo "Tenant: {$tenant->id}, Plan: {$plan->name}\n";

// Find an enabled feature
$enabledFeature = null;
foreach (Feature::all() as $f) {
    if ($plan->hasFeature($f->key)) {
        $enabledFeature = $f;
        break;
    }
}

if (!$enabledFeature) {
    echo "No enabled features found for this plan. Checking manual attachment...\n";
    // Force attach one for testing
    $enabledFeature = Feature::where('key', 'sms_notifications')->first();
    $plan->features()->syncWithoutDetaching([$enabledFeature->id => ['value_boolean' => true]]);
    echo "Force enabled sms_notifications for " . $plan->name . "\n";
}

echo "Testing Feature: {$enabledFeature->key}, Status: {$enabledFeature->implementation_status}\n";
echo "Initial hasPlanFeature: " . ($tenant->hasPlanFeature($enabledFeature->key) ? 'Yes' : 'No') . "\n";

// Change status to maintenance
$oldStatus = $enabledFeature->implementation_status;
$enabledFeature->update(['implementation_status' => Feature::STATUS_MAINTENANCE]);
echo "Changed status to: maintenance\n";

// Re-fetch everything
$tenant = Tenant::first();
$plan = $tenant->subscription->plan;
$plan->unsetRelation('features'); // Ensure fresh reload
echo "hasPlanFeature (Maintenance): " . ($tenant->hasPlanFeature($enabledFeature->key) ? 'Yes' : 'No') . "\n";

// Restore
$enabledFeature->update(['implementation_status' => $oldStatus]);
echo "Restored feature\n";
