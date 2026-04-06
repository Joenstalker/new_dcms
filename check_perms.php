<?php

use App\Models\User;
use Stancl\Tenancy\Features\TinkerLoop;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

tenancy()->initialize('junjunsmile');

$user = User::where('email', 'joenilpanal+dentist1@gmail.com')->first();

if (!$user) {
    echo "User not found\n";
    exit;
}

echo "User: " . $user->name . "\n";
echo "Roles: " . json_encode($user->getRoleNames()->toArray()) . "\n";
echo "Permissions: " . json_encode($user->getAllPermissions()->pluck('name')->toArray()) . "\n";
