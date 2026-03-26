<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use Database\Seeders\DashboardPermissionSeeder;

$tenantId = 'rhodsmile';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    die("Tenant $tenantId not found\n");
}

tenancy()->initialize($tenant);

$seeder = new DashboardPermissionSeeder();
$seeder->run();

echo "DashboardPermissionSeeder executed successfully for tenant: $tenantId\n";
