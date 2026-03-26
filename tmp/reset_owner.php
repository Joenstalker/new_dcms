<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\User;

$tenantId = 'rhodsmile';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    die("Tenant $tenantId not found\n");
}

tenancy()->initialize($tenant);

$ownerEmail = 'rhodanthony190605@gmail.com';
$user = User::where('email', $ownerEmail)->first();

if ($user) {
    $user->update(['password' => bcrypt('password')]);
    echo "User $ownerEmail password reset to 'password'.\n";
} else {
    echo "User $ownerEmail not found in tenant $tenantId.\n";
}
