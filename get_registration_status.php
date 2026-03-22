<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== TENANT REGISTRATION STATUS ===\n\n";

$tenants = Tenant::orderBy('created_at', 'desc')->take(5)->get();

if ($tenants->isEmpty()) {
    echo "No tenants found.\n";
    exit;
}

foreach ($tenants as $tenant) {
    echo "Tenant ID: " . $tenant->id . "\n";
    echo "Created: " . $tenant->created_at . "\n";
    echo "Status: " . ($tenant->status ?? 'unknown') . "\n";
    
    $domain = $tenant->domains()->first();
    echo "Subdomain: " . ($domain ? $domain->domain : 'No subdomain set') . "\n";

    echo "Database: " . ($tenant->database_name ?? 'Not set') . "\n";
    
    try {
        tenancy()->initialize($tenant);
        $userCount = User::count();
        echo "Users in Tenant DB: " . $userCount . "\n";
        
        $users = User::all();
        foreach ($users as $user) {
            echo " - " . $user->email . " (Roles: " . implode(', ', $user->getRoleNames()->toArray()) . ")\n";
        }
        tenancy()->end();
    } catch (\Exception $e) {
        echo "ERROR: Could not access tenant database: " . $e->getMessage() . "\n";
    }
    
    echo str_repeat("-", 30) . "\n";
}
