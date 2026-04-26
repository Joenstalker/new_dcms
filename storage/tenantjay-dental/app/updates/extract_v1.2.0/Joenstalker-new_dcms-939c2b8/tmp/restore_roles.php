<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Restore Owner
$owner = User::find(1);
if ($owner) {
    if (!Role::where('name', 'Owner')->exists()) {
        Role::create(['name' => 'Owner']);
    }
    $owner->assignRole('Owner');
    echo "Restored Owner role to " . $owner->name . "\n";
}

// Restore Staff (Guessing roles based on email or name for now, or just re-assigning manually if we know them)
// For this debug, I'll just check all users
$users = User::where('id', '>', 1)->get();
foreach ($users as $user) {
    // We can't be sure of their role without meta, but we can check if they had one
    // Since it's a small team, let's just make sure they have A role or wait for Admin to fix
    echo "User " . $user->name . " (ID: " . $user->id . ") still has no role. Admin must re-assign.\n";
}
