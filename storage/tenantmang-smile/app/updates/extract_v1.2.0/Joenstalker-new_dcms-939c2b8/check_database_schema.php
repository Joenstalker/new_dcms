<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking database schema ===" . PHP_EOL;

// Check which database has the patients table
try {
    $centralPatientCount = DB::connection('central')->table('patients')->count();
    echo 'Patients in central DB: ' . $centralPatientCount . PHP_EOL;
} catch (Exception $e) {
    echo 'Central DB query failed: ' . $e->getMessage() . PHP_EOL;
}

// Try to check tenant database
$tenant = \App\Models\Tenant::find('junjunsmile');
if ($tenant) {
    echo 'Tenant database name: ' . $tenant->getAttribute('database_name') . PHP_EOL;
    
    try {
        // Check if the database exists and has tables
        $dbName = $tenant->getAttribute('database_name');
        $result = DB::connection('central')->select('SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = ?', [$dbName]);
        echo 'Tables in ' . $dbName . ': ' . count($result) . ' tables found' . PHP_EOL;
        
        if (count($result) > 0) {
            echo 'First few tables:' . PHP_EOL;
            foreach (array_slice($result, 0, 5) as $row) {
                echo '  - ' . $row->TABLE_NAME . PHP_EOL;
            }
        }
    } catch (Exception $e) {
        echo 'Info schema query failed: ' . $e->getMessage() . PHP_EOL;
    }
}
