<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Feature;
use App\Models\SubscriptionPlan;

echo "=== FEATURE AUDIT ===\n";
$features = Feature::all();
echo "Defined Features in 'features' table:\n";
foreach ($features as $f) {
    echo " - [{$f->key}] ({$f->type}): {$f->name}\n";
}

echo "\n=== SUBSCRIPTION PLAN COLUMNS ===\n";
$plans = SubscriptionPlan::first();
if ($plans) {
    $attributes = array_keys($plans->getAttributes());
    $legacyColumns = array_filter($attributes, function($col) {
        return str_starts_with($col, 'has_') || str_starts_with($col, 'max_') || $col === 'report_level';
    });
    
    foreach ($legacyColumns as $col) {
        echo " - $col\n";
    }
} else {
    echo "No plans found.\n";
}
