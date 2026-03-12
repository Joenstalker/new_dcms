<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Assuming tenant 'foo'
$tenant = \App\Models\Tenant::find('foo');
tenancy()->initialize($tenant);

$user = User::first();
if ($user) {
    echo "User: " . $user->email . "\n";
    echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    echo "Permissions (" . count($user->getAllPermissions()) . "):\n";
    foreach ($user->getAllPermissions()->pluck('name')->toArray() as $p) {
        echo " - $p\n";
    }
}
