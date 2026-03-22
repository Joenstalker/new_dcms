<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$tenantId = 'joenil';
$tenant = Tenant::find($tenantId);

if ($tenant) {
    echo "=== CHECKING ROLES IN JOENIL TENANT ===\n";
    try {
        tenancy()->initialize($tenant);
        $roleCount = DB::table('roles')->count();
        echo "Roles count: " . $roleCount . "\n";
        
        if ($roleCount > 0) {
            $roles = DB::table('roles')->pluck('name')->toArray();
            echo "Roles: " . implode(', ', $roles) . "\n";
        } else {
            echo " [!] CRITICAL: No roles found! Maybe migrations/seeders failed.\n";
        }
        tenancy()->end();
    } catch (\Exception $e) {
        echo "ERROR initializing tenancy: " . $e->getMessage() . "\n";
    }
}
