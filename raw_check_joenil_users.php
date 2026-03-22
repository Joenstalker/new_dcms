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
    echo "=== RAW SQL CHECK: JOENIL USERS ===\n";
    try {
        tenancy()->initialize($tenant);
        $users = DB::table('users')->get();
        echo "Raw count in 'users' table: " . $users->count() . "\n";
        
        foreach ($users as $user) {
            echo " - ID: " . $user->id . " | Email: " . $user->email . "\n";
        }
        tenancy()->end();
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
