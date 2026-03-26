<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use Spatie\Permission\Models\Role;

$tenantId = 'rhodsmile';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    die("Tenant $tenantId not found\n");
}

tenancy()->initialize($tenant);

foreach (['Dentist', 'Assistant'] as $roleName) {
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $role->syncPermissions([]); // Clear all
        echo "Cleared all permissions for Role: $roleName\n";
    }
}
