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
if (!$tenant) {
    die("Tenant foo not found\n");
}

tenancy()->initialize($tenant);

$user = User::first();
if ($user) {
    echo "User: " . $user->email . "\n";
    echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    echo "Permissions: " . implode(', ', $user->getAllPermissions()->pluck('name')->toArray()) . "\n";
} else {
    echo "No user found in tenant foo\n";
}
