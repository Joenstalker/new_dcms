<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$email = '2301107552@student.buksu.edu.ph';
echo "Checking central users for '$email'...\n";

$user = DB::connection('central')->table('users')->where('email', $email)->first();

if ($user) {
    echo "FOUND in central 'users' table!\n";
    echo "ID: " . $user->id . "\n";
    echo "Created: " . $user->created_at . "\n";
} else {
    echo "NOT FOUND in central 'users' table.\n";
}

// Also check default connection just in case
$userDefault = DB::table('users')->where('email', $email)->first();
if ($userDefault && (!$user || $userDefault->id !== $user->id)) {
    echo "FOUND in default connection 'users' table!\n";
}
