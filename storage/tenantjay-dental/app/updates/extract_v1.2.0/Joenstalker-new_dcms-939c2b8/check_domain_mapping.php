<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Stancl\Tenancy\Database\Models\Domain;
use App\Models\Tenant;

echo "=== Domain Resolution Check ===" . PHP_EOL;

// Find the domain
$domain = Domain::where('domain', 'junjunsmile.localhost')->first();

if ($domain) {
    echo 'Domain found: junjunsmile.localhost' . PHP_EOL;
    echo 'Tenant ID: ' . $domain->tenant_id . PHP_EOL;
    
    // Get the tenant
    $tenant = Tenant::find($domain->tenant_id);
    if ($tenant) {
        echo 'Tenant name: ' . $tenant->name . PHP_EOL;
        echo 'Tenant database: ' . $tenant->database_name . PHP_EOL;
    }
} else {
    echo 'Domain NOT found: junjunsmile.localhost' . PHP_EOL;
    
    // List all domains
    echo 'Available domains:' . PHP_EOL;
    Domain::all()->each(function ($d) {
        echo ' - ' . $d->domain . ' -> ' . $d->tenant_id . PHP_EOL;
    });
}
