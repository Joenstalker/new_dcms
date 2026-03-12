<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Assuming tenant 'foo'
$tenant = \App\Models\Tenant::find('foo');
if (!$tenant) {
    die("Tenant foo not found\n");
}

tenancy()->initialize($tenant);

echo "Seeding tenant: foo\n";
$seeder = new RolesAndPermissionsSeeder();
$seeder->run();
echo "Seeding complete.\n";

$user = User::first();
if ($user) {
    echo "User: " . $user->email . "\n";
    echo "Role: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    echo "Permissions count: " . count($user->getAllPermissions()) . "\n";
    
    $hasEditStaff = $user->hasPermissionTo('edit staff');
    echo "Has 'edit staff' permission: " . ($hasEditStaff ? 'YES' : 'NO') . "\n";
}
