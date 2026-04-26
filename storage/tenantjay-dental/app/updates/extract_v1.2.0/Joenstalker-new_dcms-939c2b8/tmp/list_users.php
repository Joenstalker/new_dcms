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

$users = User::all();
foreach ($users as $u) {
    $roles = $u->getRoleNames()->join(', ');
    echo "{$u->email} | Roles: {$roles}\n";
}
