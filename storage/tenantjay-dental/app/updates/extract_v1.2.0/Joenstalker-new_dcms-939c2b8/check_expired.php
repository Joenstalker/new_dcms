<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PendingRegistration;

$all = PendingRegistration::all();
echo "Total count: " . $all->count() . PHP_EOL;
foreach ($all as $r) {
    echo "ID: {$r->id}, Status: {$r->status}, Subdomain: {$r->subdomain}, Created: {$r->created_at}, Expires: {$r->expires_at}, Timeout: {$r->pending_timeout_minutes}, Now (UTC): " . now('UTC') . PHP_EOL;
}
