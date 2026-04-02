<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Feature;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Find a feature (e.g., patient records or sms_notifications)
$feature = Feature::where('key', 'sms_notifications')->first();
if (!$feature) {
    echo "Feature not found\n";
    exit;
}

echo "Feature: {$feature->key}, Status: {$feature->implementation_status}, Active: " . ($feature->is_active ? 'Yes' : 'No') . "\n";

// 2. Find a tenant and their plan
$tenant = Tenant::first();
if (!$tenant || !$tenant->subscription || !$tenant->subscription->plan) {
    echo "Tenant or Plan not found\n";
    exit;
}
$plan = $tenant->subscription->plan;

echo "Tenant: {$tenant->id}, Plan: {$plan->name}\n";

// 3. Test hasPlanFeature
echo "Initial hasPlanFeature: " . ($tenant->hasPlanFeature($feature->key) ? 'Yes' : 'No') . "\n";

// 4. Change status to maintenance
$oldStatus = $feature->implementation_status;
$feature->update(['implementation_status' => Feature::STATUS_MAINTENANCE]);
echo "Changed status to: " . Feature::STATUS_MAINTENANCE . "\n";

// Re-fetch to clear any caching (though it's on central DB)
$tenant = Tenant::first();
echo "hasPlanFeature (Maintenance): " . ($tenant->hasPlanFeature($feature->key) ? 'Yes' : 'No') . "\n";

// 5. Change is_active to false
$feature->update(['is_active' => false]);
echo "Changed is_active to: false\n";

$tenant = Tenant::first();
echo "hasPlanFeature (Inactive): " . ($tenant->hasPlanFeature($feature->key) ? 'Yes' : 'No') . "\n";

// Restore
$feature->update([
    'implementation_status' => $oldStatus,
    'is_active' => true
]);
echo "Restored feature\n";
