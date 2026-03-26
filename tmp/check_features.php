<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = Tenant::first();
if (!$tenant) {
    echo "NO_TENANT_FOUND";
    exit;
}

echo "Tenant ID: " . $tenant->id . "\n";
echo "Can Customize Branding: " . ($tenant->canCustomizeBranding() ? 'YES' : 'NO') . "\n";
echo "Branding Color: " . ($tenant->branding_color ?? 'NULL') . "\n";

$subscription = $tenant->subscription;
if ($subscription) {
    echo "Plan Name: " . ($subscription->plan->name ?? 'N/A') . "\n";
    echo "Plan Features: " . json_encode($subscription->plan->features ?? []) . "\n";
    echo "Has custom_branding feature: " . ($subscription->plan->hasFeature('custom_branding') ? 'YES' : 'NO') . "\n";
} else {
    echo "NO_SUBSCRIPTION_FOUND\n";
}

// Check the DB data column directly
$dbData = DB::table('tenants')->where('id', $tenant->id)->value('data');
echo "Raw DB Data: " . $dbData . "\n";
