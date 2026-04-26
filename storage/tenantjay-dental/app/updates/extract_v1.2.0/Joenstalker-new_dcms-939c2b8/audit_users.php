<?php

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

ob_start();

echo str_repeat("=", 50) . "\n";
echo "           USER AUDIT REPORT\n";
echo str_repeat("=", 50) . "\n\n";

// --- CENTRAL DATABASE ---
echo "[CENTRAL DATABASE]\n";
echo str_repeat("-", 20) . "\n";
$centralUsers = User::where('is_admin', true)->get();
if ($centralUsers->isEmpty()) {
    echo "No Super Admins found.\n";
} else {
    foreach ($centralUsers as $user) {
        echo sprintf(" - %-30s | Admin: %s\n", $user->email, $user->is_admin ? 'YES' : 'NO');
    }
}
echo "\n";

// --- TENANT DATABASES ---
echo "[TENANT DATABASES]\n";
echo str_repeat("-", 20) . "\n";

$tenants = Tenant::all();

if ($tenants->isEmpty()) {
    echo "No tenants found.\n";
} else {
    foreach ($tenants as $tenant) {
        echo "TENANT: " . $tenant->id . " (" . ($tenant->domains()->first() ? $tenant->domains()->first()->domain : 'no domain') . ")\n";
        
        try {
            tenancy()->initialize($tenant);
            
            $tenantUsers = User::all();
            if ($tenantUsers->isEmpty()) {
                echo "   No users found.\n";
            } else {
                foreach ($tenantUsers as $user) {
                    $roles = $user->getRoleNames()->toArray();
                    echo sprintf("   - %-30s | Roles: %s\n", $user->email, empty($roles) ? 'NONE' : implode(', ', $roles));
                }
            }
            
            tenancy()->end();
        } catch (\Exception $e) {
            echo "   ERROR: Could not access tenant database: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
}

echo str_repeat("=", 50) . "\n";
echo "Audit Complete.\n";
echo str_repeat("=", 50) . "\n";

$output = ob_get_clean();
echo $output;
file_put_contents('audit_results.txt', $output);
