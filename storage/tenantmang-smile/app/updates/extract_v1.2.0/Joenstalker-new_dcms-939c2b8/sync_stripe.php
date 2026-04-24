<?php

// Boot Laravel explicitly
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SubscriptionPlan;
use App\Http\Controllers\Admin\PlanController;

echo "Starting Stripe Sync for all plans...\n";

$controller = app()->make(PlanController::class);
$method = new ReflectionMethod($controller, 'syncWithStripe');
$method->setAccessible(true);

$plans = SubscriptionPlan::all();

foreach ($plans as $plan) {
    if (!$plan->stripe_product_id) {
        try {
            $method->invoke($controller, $plan);
            echo "SUCCESS: Synced {$plan->name} with Stripe!\n";
        } catch (\Exception $e) {
            echo "ERROR synchronizing {$plan->name}: {$e->getMessage()}\n";
        }
    } else {
        echo "SKIPPED: {$plan->name} already has a Stripe Product ID.\n";
    }
}

echo "Done.\n";
