<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$tenantId = 'joenil';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    echo "Tenant '$tenantId' NOT FOUND.\n";
    exit;
}

echo "Fixing tenant '$tenantId'...\n";
echo "Old database_name: " . $tenant->database_name . "\n";

$newDbName = 'tenant_joenil_db';

try {
    DB::beginTransaction();
    
    // Update both database_name and database (Stancl's standard attribute)
    $tenant->database_name = $newDbName;
    $tenant->database = $newDbName;
    $tenant->save();
    
    DB::commit();
    echo "SUCCESS! Tenant record updated to '$newDbName'.\n";
    
    // Verify login readiness
    echo "\nVerifying user access...\n";
    tenancy()->initialize($tenant);
    $userCount = \App\Models\User::count();
    echo "User count in tenant DB: " . $userCount . "\n";
    if ($userCount > 0) {
        $user = \App\Models\User::first();
        echo "Valid user found: " . $user->email . "\n";
        echo "Tenant is now READY for login.\n";
    } else {
        echo " [!] WARNING: No user found even after fix. Might need to recreate user.\n";
    }
    tenancy()->end();
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
