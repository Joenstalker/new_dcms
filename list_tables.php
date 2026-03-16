<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Current Connection Name: " . DB::connection()->getName() . "\n";
echo "Current Database: " . DB::connection()->getDatabaseName() . "\n";

$tables = DB::select('SHOW TABLES');
echo "Tables in " . DB::connection()->getDatabaseName() . ":\n";
foreach ($tables as $table) {
    echo " - " . current((array)$table) . "\n";
}

$columns = DB::select('SHOW COLUMNS FROM users');
if ($columns) {
    echo "\nColumns in users table:\n";
    foreach ($columns as $column) {
        echo " - " . $column->Field . " (" . $column->Type . ")\n";
    }
}

$userCount = DB::table('users')->count();
echo "\nRaw User count via DB facade: " . $userCount . "\n";
