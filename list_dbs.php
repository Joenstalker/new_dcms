<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$databases = DB::select('SHOW DATABASES');
echo "Databases on this host:\n";
foreach ($databases as $db) {
    echo " - " . current((array)$db) . "\n";
}
