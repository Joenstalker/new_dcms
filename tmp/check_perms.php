<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

$user = User::where('email', '!=', 'admin@example.com')->first();
if (!$user) {
    echo "No staff user found\n";
    exit;
}

echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
echo "Direct Permissions: " . json_encode($user->permissions()->pluck('name')) . "\n";
echo "All Permissions: " . json_encode($user->getAllPermissions()->pluck('name')) . "\n";
echo "Roles: " . json_encode($user->getRoleNames()) . "\n";
echo "Guard Name: " . $user->guard_name . "\n";
