<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

echo "=== Testing Tenancy Initialization ===" . PHP_EOL;

$tenant = Tenant::find('junjunsmile');
echo 'Tenant ID: ' . $tenant->id . PHP_EOL;
echo 'Tenant DB Name: ' . $tenant->database_name . PHP_EOL;
echo 'Tenant Connection: ' . $tenant->getDatabaseConnectionName() . PHP_EOL;

// Check if connection exists in config
$connections = array_keys(config('database.connections', []));
echo 'Available connections in config: ' . json_encode($connections) . PHP_EOL;

// Now initialize tenancy
echo PHP_EOL . '=== Initializing Tenancy ===' . PHP_EOL;
try {
    tenancy()->initialize($tenant);
    echo 'Tenancy initialized successfully' . PHP_EOL;
    echo 'Current tenant: ' . tenant()?->id . PHP_EOL;
    
    // Check connections again
    $connections = array_keys(config('database.connections', []));
    echo 'Available connections after init: ' . json_encode($connections) . PHP_EOL;
    
    // Check default connection
    $defaultConnection = config('database.default');
    echo 'Default connection: ' . $defaultConnection . PHP_EOL;
    
    // Try to query patients
    echo PHP_EOL . '=== Testing Patient Query ===' . PHP_EOL;
    try {
        $count = DB::table('patients')->count();
        echo 'Patient count: ' . $count . PHP_EOL;
    } catch (Exception $e) {
        echo 'Query failed: ' . $e->getMessage() . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Tenancy initialization failed: ' . $e->getMessage() . PHP_EOL;
}

tenancy()->end();
