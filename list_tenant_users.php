<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TENANT DATABASE USERS ===\n";
try {
    $users = DB::select("SELECT User, Host FROM mysql.user WHERE User LIKE 'tenant_user_%'");
    foreach ($users as $user) {
        echo "User: " . $user->User . " @ " . $user->Host . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
