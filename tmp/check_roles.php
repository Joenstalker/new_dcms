<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "Roles in Database:\n";
print_r(Role::pluck('name')->toArray());

echo "\nPermissions in Database:\n";
print_r(Permission::pluck('name')->toArray());

echo "\nUser ID 1 (" . User::find(1)->name . ") Roles:\n";
print_r(User::find(1)->getRoleNames()->toArray());

echo "\nModel Has Roles Count: " . \Illuminate\Support\Facades\DB::table('model_has_roles')->count() . "\n";
