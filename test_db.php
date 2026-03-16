<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Tenant;

echo "Default Connection: " . config('database.default') . "\n";
echo "MySQL Database: " . config('database.connections.mysql.database') . "\n";
echo "Central Database: " . config('database.connections.central.database') . "\n";
echo "Current Connection Database: " . DB::connection()->getDatabaseName() . "\n";

echo "\nUser count: " . User::count() . "\n";
echo "Admin count: " . User::where('is_admin', true)->count() . "\n";
echo "Tenant count: " . Tenant::count() . "\n";

$users = User::all();
foreach ($users as $u) {
    echo "User: {$u->email} (Admin: " . ($u->is_admin ? 'Yes' : 'No') . ")\n";
}
