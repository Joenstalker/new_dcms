<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$plainDb = 'tenant_joenil_db';
$hashedDb = 'tenant_309f8cf59e6dc668_db';

echo "Checking database '$plainDb'...\n";
$plainExists = DB::select("SHOW DATABASES LIKE '$plainDb'");
if ($plainExists) {
    echo "Database '$plainDb' EXISTS.\n";
    $userCount = DB::connection('mysql')->select("SELECT count(*) as count FROM `$plainDb`.users")[0]->count ?? 0;
    echo "Users in '$plainDb': " . $userCount . "\n";
    if ($userCount > 0) {
        $users = DB::connection('mysql')->select("SELECT email FROM `$plainDb`.users");
        foreach ($users as $user) {
            echo " - " . $user->email . "\n";
        }
    }
} else {
    echo "Database '$plainDb' DOES NOT exist.\n";
}

echo "\nChecking database '$hashedDb'...\n";
$hashedExists = DB::select("SHOW DATABASES LIKE '$hashedDb'");
if ($hashedExists) {
    echo "Database '$hashedDb' EXISTS.\n";
    $userCount = DB::connection('mysql')->select("SELECT count(*) as count FROM `$hashedDb`.users")[0]->count ?? 0;
    echo "Users in '$hashedDb': " . $userCount . "\n";
} else {
    echo "Database '$hashedDb' DOES NOT exist.\n";
}
