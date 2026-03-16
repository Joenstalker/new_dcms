<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['dcms_new_db', 'dcms_saas_new'] as $dbName) {
    try {
        echo "\nChecking Database: $dbName\n";
        $tables = DB::connection('mysql')->select("SHOW TABLES FROM `$dbName` ");
        echo "Tables:\n";
        foreach ($tables as $t) {
            echo " - " . current((array)$t) . "\n";
        }
        
        $userCount = DB::connection('mysql')->table("$dbName.users")->count();
        echo "User count in $dbName: $userCount\n";
        
        $tenantCount = DB::connection('mysql')->table("$dbName.tenants")->count();
        echo "Tenant count in $dbName: $tenantCount\n";
    } catch (\Exception $e) {
        echo "Error checking $dbName: " . $e->getMessage() . "\n";
    }
}
