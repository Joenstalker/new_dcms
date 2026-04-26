<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Permission;

$name = 'view dashboard';
$exists = Permission::where('name', $name)->exists();
echo "Permission [$name] exists: " . ($exists ? 'YES' : 'NO') . PHP_EOL;
